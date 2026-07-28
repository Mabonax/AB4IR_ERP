#!/usr/bin/env python3
"""
Download South African Municipal Demarcation Board (MDB) municipality and ward data
from the public ArcGIS REST FeatureServer.

Outputs:
  - mdb_local_municipalities_attributes.csv
  - mdb_wards_attributes.csv
  - mdb_local_municipalities.geojson
  - mdb_wards.geojson

Run:
  python download_mdb_sa_geography.py

Requires:
  pip install requests
"""

from __future__ import annotations

import csv
import json
import time
from pathlib import Path
from typing import Any, Dict, Iterable, List
from urllib.parse import urlencode

import requests

BASE = "https://csggis.drdlr.gov.za/server/rest/services/Hosted/MDB/FeatureServer"
LAYERS = {
    "local_municipalities": 4,
    "wards": 20,
}
OUT_DIR = Path(__file__).resolve().parent / "output"
OUT_DIR.mkdir(exist_ok=True)


def query_layer(layer_id: int, *, offset: int = 0, count: int = 1000, geometry: bool = False) -> Dict[str, Any]:
    params = {
        "where": "1=1",
        "outFields": "*",
        "returnGeometry": "true" if geometry else "false",
        "f": "geojson" if geometry else "json",
        "resultOffset": offset,
        "resultRecordCount": count,
    }
    url = f"{BASE}/{layer_id}/query?{urlencode(params)}"
    response = requests.get(url, timeout=120)
    response.raise_for_status()
    return response.json()


def fetch_all_features(layer_id: int, *, geometry: bool = False, page_size: int = 1000) -> List[Dict[str, Any]]:
    features: List[Dict[str, Any]] = []
    offset = 0

    while True:
        data = query_layer(layer_id, offset=offset, count=page_size, geometry=geometry)
        page = data.get("features", [])
        if not page:
            break

        features.extend(page)
        print(f"Layer {layer_id}: downloaded {len(features)} records")

        # ArcGIS may return exceededTransferLimit=true when more data is available.
        if not data.get("exceededTransferLimit") and len(page) < page_size:
            break

        offset += page_size
        time.sleep(0.25)

    return features


def write_attributes_csv(name: str, features: Iterable[Dict[str, Any]]) -> None:
    rows = []
    for feature in features:
        attrs = feature.get("attributes") or feature.get("properties") or {}
        rows.append(attrs)

    if not rows:
        print(f"No rows for {name}")
        return

    fieldnames = sorted({key for row in rows for key in row.keys()})
    path = OUT_DIR / f"mdb_{name}_attributes.csv"
    with path.open("w", newline="", encoding="utf-8") as f:
        writer = csv.DictWriter(f, fieldnames=fieldnames)
        writer.writeheader()
        writer.writerows(rows)
    print(f"Wrote {path}")


def write_geojson(name: str, features: List[Dict[str, Any]]) -> None:
    fc = {"type": "FeatureCollection", "features": features}
    path = OUT_DIR / f"mdb_{name}.geojson"
    with path.open("w", encoding="utf-8") as f:
        json.dump(fc, f, ensure_ascii=False)
    print(f"Wrote {path}")


def main() -> None:
    for name, layer_id in LAYERS.items():
        # Attribute-only CSV, lighter and ideal for seeding relational tables.
        attr_features = fetch_all_features(layer_id, geometry=False)
        write_attributes_csv(name, attr_features)

        # GeoJSON with boundaries, larger but useful for maps/spatial lookup.
        geo_features = fetch_all_features(layer_id, geometry=True)
        write_geojson(name, geo_features)


if __name__ == "__main__":
    main()

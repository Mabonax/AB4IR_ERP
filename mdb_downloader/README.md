# MDB South Africa Geography Downloader

This script downloads public Municipal Demarcation Board (MDB) geography data from the ArcGIS REST FeatureServer.

It downloads:

- Local Municipalities: layer `4`
- Wards: layer `20`

Outputs are saved in `output/`:

- `mdb_local_municipalities_attributes.csv`
- `mdb_wards_attributes.csv`
- `mdb_local_municipalities.geojson`
- `mdb_wards.geojson`

## Run

```bash
pip install requests
python download_mdb_sa_geography.py
```

## Source service

```text
https://csggis.drdlr.gov.za/server/rest/services/Hosted/MDB/FeatureServer
```

CSV/JSON downloads are paginated because ArcGIS limits the layer response to 1,000 records per request.

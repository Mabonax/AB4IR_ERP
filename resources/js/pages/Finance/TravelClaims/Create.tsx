import { Head, Link, useForm } from "@inertiajs/react";

import { DomainNav } from "@/components/domain-nav";
import { financeNavItems } from "@/config/domain-nav/finance";
import AppLayout from "@/layouts/app-layout";
import { type BreadcrumbItem } from "@/types";

const breadcrumbs: BreadcrumbItem[] = [
  { title: "Finance", href: "/finance/travel-claims" },
  { title: "New Travel Claim", href: "/finance/travel-claims/create" },
];

export default function TravelClaimCreate({
  claimants,
  defaultTariffPerKm,
}: {
  claimants: { id: number; name: string; department_name: string | null }[];
  defaultTariffPerKm: number;
}) {
  const form = useForm({
    claimant_staff_member_id: claimants[0]?.id ? String(claimants[0].id) : "",
    claim_month: "",
    claimant_address: "",
    vehicle_make_model: "",
    vehicle_type: "Passenger",
    vehicle_year: "",
    engine_volume: "",
    tariff_per_km: String(defaultTariffPerKm),
    home_distance_km: "0",
    trips: [
      {
        travel_date: "",
        route_from: "",
        route_to: "",
        start_time: "",
        end_time: "",
        nature_of_duty: "",
        actual_distance_km: "",
        claimable_distance_km: "",
      },
    ],
  });

  const addTrip = () => {
    form.setData("trips", [
      ...form.data.trips,
      {
        travel_date: "",
        route_from: "",
        route_to: "",
        start_time: "",
        end_time: "",
        nature_of_duty: "",
        actual_distance_km: "",
        claimable_distance_km: "",
      },
    ]);
  };

  const removeTrip = (index: number) => {
    form.setData(
      "trips",
      form.data.trips.filter((_, itemIndex) => itemIndex !== index),
    );
  };

  const updateTrip = (index: number, key: string, value: string) => {
    form.setData(
      "trips",
      form.data.trips.map((trip, itemIndex) =>
        itemIndex === index ? { ...trip, [key]: value } : trip,
      ),
    );
  };

  const totalAmount = form.data.trips.reduce((sum, trip) => {
    const claimable = Number(trip.claimable_distance_km || 0);
    const tariff = Number(form.data.tariff_per_km || 0);
    return sum + claimable * tariff;
  }, 0);

  return (
    <AppLayout breadcrumbs={breadcrumbs}>
      <Head title="New Travel Claim" />

      <div className="space-y-5 p-4">
        <div className="flex flex-wrap items-center justify-between gap-3">
          <div>
            <h1 className="text-xl font-semibold">New Travel Claim</h1>
            <p className="text-sm text-muted-foreground">
              Capture the transport claim template and submit it for executive approval before finance processing.
            </p>
          </div>
          <DomainNav items={financeNavItems} />
        </div>

        <form
          className="space-y-5"
          onSubmit={(e) => {
            e.preventDefault();
            form.post("/finance/travel-claims");
          }}
        >
          <div className="grid gap-4 rounded-xl border bg-card p-4 shadow-sm md:grid-cols-2 xl:grid-cols-4">
            <div>
              <label className="mb-1 block text-sm font-medium">Claimant</label>
              <div className="flex min-h-10 w-full items-center rounded-md border border-input bg-muted/40 px-3 py-2 text-sm">
                {claimants[0]
                  ? `${claimants[0].name}${claimants[0].department_name ? ` (${claimants[0].department_name})` : ""}`
                  : "No eligible claimant profile found"}
              </div>
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Claim Month</label>
              <input
                type="month"
                value={form.data.claim_month}
                onChange={(e) => form.setData("claim_month", e.currentTarget.value ? `${e.currentTarget.value}-01` : "")}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Vehicle Make and Model</label>
              <input
                value={form.data.vehicle_make_model}
                onChange={(e) => form.setData("vehicle_make_model", e.currentTarget.value)}
                placeholder="Optional"
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Vehicle Type</label>
              <input
                value={form.data.vehicle_type}
                onChange={(e) => form.setData("vehicle_type", e.currentTarget.value)}
                placeholder="Optional"
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Year Manufactured</label>
              <input
                type="number"
                value={form.data.vehicle_year}
                onChange={(e) => form.setData("vehicle_year", e.currentTarget.value)}
                placeholder="Optional"
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Engine Volume</label>
              <input
                value={form.data.engine_volume}
                onChange={(e) => form.setData("engine_volume", e.currentTarget.value)}
                placeholder="Optional"
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Tariff per KM</label>
              <input
                type="number"
                step="0.01"
                value={form.data.tariff_per_km}
                onChange={(e) => form.setData("tariff_per_km", e.currentTarget.value)}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div>
              <label className="mb-1 block text-sm font-medium">Home Distance to Work (KM)</label>
              <input
                type="number"
                step="0.01"
                value={form.data.home_distance_km}
                onChange={(e) => form.setData("home_distance_km", e.currentTarget.value)}
                className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
            <div className="md:col-span-2 xl:col-span-4">
              <label className="mb-1 block text-sm font-medium">Claimant Address</label>
              <textarea
                rows={2}
                value={form.data.claimant_address}
                onChange={(e) => form.setData("claimant_address", e.currentTarget.value)}
                className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
              />
            </div>
          </div>

          <div className="rounded-xl border bg-card p-4 shadow-sm">
            <div className="mb-3 flex items-center justify-between gap-3">
              <div>
                <h2 className="font-semibold">Trip Schedule</h2>
                <p className="text-sm text-muted-foreground">
                  Mirror the template rows and let the system total the claim.
                </p>
              </div>
              <button
                type="button"
                onClick={addTrip}
                className="rounded-md border border-orange-500 px-3 py-2 text-sm text-orange-600 hover:bg-orange-500 hover:text-white"
              >
                Add Trip
              </button>
            </div>

            <div className="space-y-4">
              {form.data.trips.map((trip, index) => (
                <div key={index} className="rounded-lg border p-4">
                  <div className="mb-3 flex items-center justify-between gap-3">
                    <h3 className="font-medium">Trip {index + 1}</h3>
                    {form.data.trips.length > 1 ? (
                      <button
                        type="button"
                        onClick={() => removeTrip(index)}
                        className="text-sm text-red-600 hover:underline"
                      >
                        Remove
                      </button>
                    ) : null}
                  </div>

                  <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-4">
                    <input type="date" value={trip.travel_date} onChange={(e) => updateTrip(index, "travel_date", e.currentTarget.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    <input placeholder="Route From" value={trip.route_from} onChange={(e) => updateTrip(index, "route_from", e.currentTarget.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    <input placeholder="Route To" value={trip.route_to} onChange={(e) => updateTrip(index, "route_to", e.currentTarget.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    <input type="time" value={trip.start_time} onChange={(e) => updateTrip(index, "start_time", e.currentTarget.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    <input type="time" value={trip.end_time} onChange={(e) => updateTrip(index, "end_time", e.currentTarget.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    <input type="number" step="0.01" placeholder="Actual Distance KM" value={trip.actual_distance_km} onChange={(e) => updateTrip(index, "actual_distance_km", e.currentTarget.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    <input type="number" step="0.01" placeholder="Claimable Distance KM" value={trip.claimable_distance_km} onChange={(e) => updateTrip(index, "claimable_distance_km", e.currentTarget.value)} className="flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm" />
                    <div className="flex h-10 items-center rounded-md border border-dashed px-3 text-sm text-muted-foreground">
                      Line Total: R{(Number(trip.claimable_distance_km || 0) * Number(form.data.tariff_per_km || 0)).toFixed(2)}
                    </div>
                    <div className="md:col-span-2 xl:col-span-4">
                      <textarea
                        rows={2}
                        placeholder="Nature of Duty (optional)"
                        value={trip.nature_of_duty}
                        onChange={(e) => updateTrip(index, "nature_of_duty", e.currentTarget.value)}
                        className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                      />
                    </div>
                  </div>
                </div>
              ))}
            </div>

            <div className="mt-4 flex items-center justify-between gap-3 rounded-lg border border-orange-200 bg-orange-50 px-4 py-3">
              <div className="text-sm text-orange-800">
                Totals are calculated from the claimable distance and tariff.
              </div>
              <div className="text-lg font-semibold text-orange-900">R{totalAmount.toFixed(2)}</div>
            </div>
          </div>

          <div className="flex items-center gap-3">
            <button
              type="submit"
              disabled={form.processing}
              className="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700 disabled:opacity-60"
            >
              Submit for Approval
            </button>
            <Link href="/finance/travel-claims" className="text-sm text-muted-foreground hover:underline">
              Cancel
            </Link>
          </div>
        </form>
      </div>
    </AppLayout>
  );
}

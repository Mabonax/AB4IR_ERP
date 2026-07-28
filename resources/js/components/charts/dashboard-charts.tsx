type HorizontalBarItem = {
  label: string;
  value: number;
  hint?: string;
  colorClass?: string;
};

type ComparisonMetric<Row> = {
  label: string;
  colorClass: string;
  value: (row: Row) => number;
};

type ComparisonBarsChartProps<Row> = {
  title: string;
  description: string;
  rows: Row[];
  rowLabel: (row: Row) => string;
  metrics: ComparisonMetric<Row>[];
  emptyMessage: string;
  maxRows?: number;
};

type StackedSegment = {
  label: string;
  value: number;
  colorClass: string;
};

type StackedCompositionChartProps = {
  title: string;
  description: string;
  segments: StackedSegment[];
  emptyMessage: string;
};

type LinePoint = {
  label: string;
  value: number;
};

type LineTrendChartProps = {
  title: string;
  description: string;
  points: LinePoint[];
  colorClass?: string;
  emptyMessage: string;
};

const defaultBarColor = "bg-red-500";

const colorMap: Record<string, string> = {
  "bg-red-500": "#ef4444",
  "bg-red-600": "#dc2626",
  "bg-amber-500": "#f59e0b",
  "bg-emerald-500": "#10b981",
  "bg-sky-500": "#0ea5e9",
  "bg-blue-500": "#3b82f6",
};

function clampPercent(value: number): number {
  return Math.max(0, Math.min(100, Number.isFinite(value) ? value : 0));
}

function humanValue(value: number): string {
  return Number.isInteger(value) ? String(value) : value.toFixed(2);
}

export function HorizontalBarChart({
  title,
  description,
  items,
  emptyMessage,
}: {
  title: string;
  description: string;
  items: HorizontalBarItem[];
  emptyMessage: string;
}) {
  const maxValue = Math.max(...items.map((item) => item.value), 0);

  return (
    <section className="rounded-2xl border bg-card p-5 shadow-sm">
      <div>
        <h2 className="text-base font-semibold">{title}</h2>
        <p className="mt-1 text-sm text-muted-foreground">{description}</p>
      </div>

      <div className="mt-5 space-y-4">
        {items.length === 0 ? (
          <div className="rounded-xl border border-dashed p-4 text-sm text-muted-foreground">{emptyMessage}</div>
        ) : (
          items.map((item) => {
            const width = maxValue > 0 ? (item.value / maxValue) * 100 : 0;

            return (
              <div key={item.label} className="space-y-2">
                <div className="flex items-end justify-between gap-3">
                  <div>
                    <div className="text-sm font-medium text-slate-900">{item.label}</div>
                    {item.hint ? <div className="text-xs text-muted-foreground">{item.hint}</div> : null}
                  </div>
                  <div className="text-sm font-semibold text-slate-900">{humanValue(item.value)}</div>
                </div>
                <div className="h-3 overflow-hidden rounded-full bg-slate-100">
                  <div
                    className={`h-full rounded-full ${item.colorClass ?? defaultBarColor}`}
                    style={{ width: `${width}%` }}
                  />
                </div>
              </div>
            );
          })
        )}
      </div>
    </section>
  );
}

export function ComparisonBarsChart<Row>({
  title,
  description,
  rows,
  rowLabel,
  metrics,
  emptyMessage,
  maxRows = 6,
}: ComparisonBarsChartProps<Row>) {
  const visibleRows = rows.slice(0, maxRows);

  return (
    <section className="rounded-2xl border bg-card p-5 shadow-sm">
      <div>
        <h2 className="text-base font-semibold">{title}</h2>
        <p className="mt-1 text-sm text-muted-foreground">{description}</p>
      </div>

      <div className="mt-4 flex flex-wrap gap-3 text-xs text-muted-foreground">
        {metrics.map((metric) => (
          <div key={metric.label} className="flex items-center gap-2">
            <span className={`inline-block h-2.5 w-2.5 rounded-full ${metric.colorClass}`} />
            <span>{metric.label}</span>
          </div>
        ))}
      </div>

      <div className="mt-5 space-y-5">
        {visibleRows.length === 0 ? (
          <div className="rounded-xl border border-dashed p-4 text-sm text-muted-foreground">{emptyMessage}</div>
        ) : (
          visibleRows.map((row, index) => (
            <div key={`${rowLabel(row)}-${index}`} className="space-y-3">
              <div className="text-sm font-medium text-slate-900">{rowLabel(row)}</div>
              <div className="space-y-2">
                {metrics.map((metric) => {
                  const value = clampPercent(metric.value(row));

                  return (
                    <div key={metric.label} className="grid items-center gap-3 sm:grid-cols-[8rem,1fr,3rem]">
                      <div className="text-xs text-muted-foreground">{metric.label}</div>
                      <div className="h-3 overflow-hidden rounded-full bg-slate-100">
                        <div
                          className={`h-full rounded-full ${metric.colorClass}`}
                          style={{ width: `${value}%` }}
                        />
                      </div>
                      <div className="text-right text-xs font-semibold text-slate-900">{value.toFixed(0)}%</div>
                    </div>
                  );
                })}
              </div>
            </div>
          ))
        )}
      </div>
    </section>
  );
}

export function StackedCompositionChart({
  title,
  description,
  segments,
  emptyMessage,
}: StackedCompositionChartProps) {
  const total = segments.reduce((sum, segment) => sum + segment.value, 0);

  return (
    <section className="rounded-2xl border bg-card p-5 shadow-sm">
      <div>
        <h2 className="text-base font-semibold">{title}</h2>
        <p className="mt-1 text-sm text-muted-foreground">{description}</p>
      </div>

      <div className="mt-5">
        {total <= 0 ? (
          <div className="rounded-xl border border-dashed p-4 text-sm text-muted-foreground">{emptyMessage}</div>
        ) : (
          <>
            <div className="flex h-5 overflow-hidden rounded-full bg-slate-100">
              {segments.map((segment) => (
                <div
                  key={segment.label}
                  className={segment.colorClass}
                  style={{ width: `${(segment.value / total) * 100}%` }}
                  title={`${segment.label}: ${segment.value}`}
                />
              ))}
            </div>
            <div className="mt-4 grid gap-3 sm:grid-cols-3">
              {segments.map((segment) => {
                const share = (segment.value / total) * 100;

                return (
                  <div key={segment.label} className="rounded-xl border p-3">
                    <div className="flex items-center gap-2 text-xs text-muted-foreground">
                      <span className={`inline-block h-2.5 w-2.5 rounded-full ${segment.colorClass}`} />
                      <span>{segment.label}</span>
                    </div>
                    <div className="mt-2 text-xl font-semibold text-slate-900">{segment.value}</div>
                    <div className="text-xs text-muted-foreground">{share.toFixed(1)}% of tracked beneficiaries</div>
                  </div>
                );
              })}
            </div>
          </>
        )}
      </div>
    </section>
  );
}

export function LineTrendChart({
  title,
  description,
  points,
  colorClass = "bg-sky-500",
  emptyMessage,
}: LineTrendChartProps) {
  const visiblePoints = points.slice(-10);
  const width = 640;
  const height = 220;
  const paddingX = 32;
  const paddingTop = 20;
  const paddingBottom = 36;
  const plotWidth = width - paddingX * 2;
  const plotHeight = height - paddingTop - paddingBottom;
  const maxValue = Math.max(...visiblePoints.map((point) => point.value), 1);
  const stroke = colorMap[colorClass] ?? "#0ea5e9";

  const coords = visiblePoints.map((point, index) => {
    const x =
      visiblePoints.length === 1
        ? width / 2
        : paddingX + (index * plotWidth) / (visiblePoints.length - 1);
    const y = paddingTop + plotHeight - (point.value / maxValue) * plotHeight;

    return { ...point, x, y };
  });

  const linePath = coords
    .map((point, index) => `${index === 0 ? "M" : "L"} ${point.x} ${point.y}`)
    .join(" ");

  return (
    <section className="rounded-2xl border bg-card p-5 shadow-sm">
      <div>
        <h2 className="text-base font-semibold">{title}</h2>
        <p className="mt-1 text-sm text-muted-foreground">{description}</p>
      </div>

      <div className="mt-5">
        {visiblePoints.length === 0 ? (
          <div className="rounded-xl border border-dashed p-4 text-sm text-muted-foreground">{emptyMessage}</div>
        ) : (
          <div className="space-y-3">
            <svg viewBox={`0 0 ${width} ${height}`} className="w-full">
              {[0, 0.25, 0.5, 0.75, 1].map((ratio) => {
                const y = paddingTop + plotHeight - ratio * plotHeight;
                const label = Math.round(ratio * maxValue);

                return (
                  <g key={ratio}>
                    <line x1={paddingX} x2={width - paddingX} y1={y} y2={y} stroke="#e5e7eb" strokeWidth="1" />
                    <text x={8} y={y + 4} fontSize="10" fill="#64748b">
                      {label}%
                    </text>
                  </g>
                );
              })}
              <path d={linePath} fill="none" stroke={stroke} strokeWidth="3" strokeLinecap="round" strokeLinejoin="round" />
              {coords.map((point) => (
                <g key={point.label}>
                  <circle cx={point.x} cy={point.y} r="4" fill={stroke} />
                  <text x={point.x} y={height - 10} textAnchor="middle" fontSize="10" fill="#64748b">
                    {point.label}
                  </text>
                </g>
              ))}
            </svg>
            <div className="text-xs text-muted-foreground">
              Measures percentage attendance captured per register date across active project locations.
            </div>
          </div>
        )}
      </div>
    </section>
  );
}

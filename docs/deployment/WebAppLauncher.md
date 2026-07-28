# Shared WebAppLauncher

The reusable Windows desktop launcher is intentionally not stored inside this Laravel application repository.

Canonical shared location:

`C:\Users\John Mabona\Documents\DEV\Shared\WebAppLauncher`

Use it when a project needs a branded Windows shell for a browser-hosted system such as Programme of Action ERP, Dr Health, PTPI Portal, or a future SaaS product.

## Reuse Workflow

1. Copy the shared launcher into the target project only when that project is ready to own launcher-specific customization.
2. Add or update a brand package under `brands/<brand-key>/`.
3. Update the brand `appsettings.json` with the target URL, browser preference, launch mode, support email, and theme assets.
4. Build and package the launcher from the shared source on a Windows machine with the .NET 9 SDK and MSIX tooling installed.

## Important

- Treat the shared location above as the source of truth.
- Do not rebuild a separate launcher scaffold ad hoc inside app repositories unless the project explicitly needs a local copy.
- If the shared launcher is enhanced for one product, push those improvements back into the shared source so other projects can reuse them.

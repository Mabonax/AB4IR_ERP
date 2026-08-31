export default function AppLogo() {
    return (
        <>
            <div className="flex aspect-square size-10 items-center justify-center">
                <img
                    src="/logo.png"
                    alt=""
                    className="size-10 object-contain"
                />
            </div>
            <div className="ml-2 grid flex-1 text-left text-sm">
                <span className="mb-0.5 truncate leading-tight font-semibold text-sidebar-foreground dark:text-white">
                    AB4IR ERP
                </span>
            </div>
        </>
    );
}

import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import { type User } from '@/types';

export function UserInfo({
    user,
    showEmail = false,
}: {
    user: User | null | undefined;
    showEmail?: boolean;
}) {
    const getInitials = useInitials();

    if (!user) {
        return (
            <>
                <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                    <AvatarFallback className="rounded-lg bg-muted text-foreground dark:bg-white/10 dark:text-white">
                        ?
                    </AvatarFallback>
                </Avatar>
                <div className="grid flex-1 text-left text-sm leading-tight">
                    <span className="truncate font-medium text-foreground dark:text-white">Guest</span>
                </div>
            </>
        );
    }

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-full">
                <AvatarImage src={user.avatar} alt={user.name} />
                <AvatarFallback className="rounded-lg bg-gradient-to-br from-zinc-700 to-zinc-900 text-xs font-semibold text-white">
                    {getInitials(user.name)}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight">
                <span className="truncate font-medium text-foreground dark:text-white">{user.name}</span>
                {showEmail && (
                    <span className="truncate text-xs text-muted-foreground dark:text-white/55">
                        {user.email}
                    </span>
                )}
            </div>
        </>
    );
}

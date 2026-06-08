import { router, usePage } from '@inertiajs/react';
import { useEffect, useMemo, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { type SharedData } from '@/types';

function getCurrentSaSnapshot(timezone: string) {
    const formatter = new Intl.DateTimeFormat('en-CA', {
        timeZone: timezone,
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    });

    const parts = Object.fromEntries(
        formatter
            .formatToParts(new Date())
            .filter((part) => part.type !== 'literal')
            .map((part) => [part.type, part.value]),
    );

    return {
        date: `${parts.year}-${parts.month}-${parts.day}`,
        time: `${parts.hour}:${parts.minute}`,
    };
}

export function StaffAttendancePrompt() {
    const { props, url } = usePage<SharedData>();
    const prompt = props.attendancePrompt;
    const [clockInOpen, setClockInOpen] = useState(false);
    const [clockOutOpen, setClockOutOpen] = useState(false);
    const [lateReason, setLateReason] = useState('');
    const [snapshot, setSnapshot] = useState(() =>
        prompt ? getCurrentSaSnapshot(prompt.timezone) : null,
    );

    useEffect(() => {
        if (!prompt) {
            return;
        }

        setSnapshot(getCurrentSaSnapshot(prompt.timezone));

        const interval = window.setInterval(() => {
            setSnapshot(getCurrentSaSnapshot(prompt.timezone));
        }, 30000);

        return () => window.clearInterval(interval);
    }, [prompt]);

    const promptKeys = useMemo(() => {
        if (!prompt) {
            return null;
        }

        return {
            clockIn: `attendance-clockin-prompt-${prompt.date}`,
            clockOut: `attendance-clockout-prompt-${prompt.date}`,
        };
    }, [prompt]);

    useEffect(() => {
        if (!prompt || !snapshot || !promptKeys) {
            return;
        }

        if (snapshot.date !== prompt.date) {
            return;
        }

        const onAttendancePage = url.startsWith('/settings/attendance');
        const hasClockedIn = Boolean(prompt.record?.clock_in_at);
        const hasClockedOut = Boolean(prompt.record?.clock_out_at);

        const attendanceWindowOpen = snapshot.time < prompt.auto_clock_out_time;

        if (!hasClockedIn && attendanceWindowOpen && !onAttendancePage) {
            const dismissed = window.sessionStorage.getItem(promptKeys.clockIn);
            if (!dismissed) {
                setClockInOpen(true);
            }
        }

        if (
            hasClockedIn &&
            !hasClockedOut &&
            prompt.can_clock_out &&
            snapshot.time >= prompt.clock_out_prompt_at &&
            snapshot.time <= prompt.auto_clock_out_time
        ) {
            const dismissed = window.sessionStorage.getItem(
                promptKeys.clockOut,
            );
            if (!dismissed) {
                setClockOutOpen(true);
            }
        }
    }, [prompt, promptKeys, snapshot, url]);

    if (!prompt || !snapshot || !promptKeys) {
        return null;
    }

    return (
        <>
            <Dialog
                open={clockInOpen}
                onOpenChange={(open) => {
                    setClockInOpen(open);

                    if (!open) {
                        window.sessionStorage.setItem(
                            promptKeys.clockIn,
                            'dismissed',
                        );
                    }
                }}
            >
                <DialogContent className="sm:max-w-[520px]">
                    <DialogHeader>
                        <DialogTitle>Clock In Required</DialogTitle>
                        <DialogDescription>
                            {prompt.staff_name}, your daily attendance has not
                            been recorded yet.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3 text-sm text-muted-foreground">
                        <p>{prompt.clock_in_message}</p>
                        <p>Clock-in cut-off: {prompt.clock_in_cutoff} SAST</p>
                        {!prompt.can_clock_in && !prompt.pending_request ? (
                            <textarea
                                rows={3}
                                value={lateReason}
                                onChange={(event) =>
                                    setLateReason(event.currentTarget.value)
                                }
                                placeholder="Explain why you are late so your line manager can review it."
                                className="w-full rounded-md border bg-card px-3 py-2 text-sm text-foreground"
                            />
                        ) : null}
                        {prompt.pending_request ? (
                            <p>
                                Request already submitted:{' '}
                                {prompt.pending_request.request_reason}
                            </p>
                        ) : null}
                    </div>

                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            onClick={() => {
                                window.sessionStorage.setItem(
                                    promptKeys.clockIn,
                                    'dismissed',
                                );
                                setClockInOpen(false);
                            }}
                        >
                            Later
                        </Button>
                        {prompt.can_clock_in ? (
                            <Button
                                onClick={() =>
                                    router.post(
                                        '/settings/attendance/clock-in',
                                        {},
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => {
                                                window.sessionStorage.setItem(
                                                    promptKeys.clockIn,
                                                    'done',
                                                );
                                                setClockInOpen(false);
                                            },
                                        },
                                    )
                                }
                            >
                                Clock In Now
                            </Button>
                        ) : prompt.pending_request ? (
                            <Button disabled>Waiting For Manager</Button>
                        ) : (
                            <Button
                                disabled={lateReason.trim() === ''}
                                onClick={() =>
                                    router.post(
                                        '/settings/attendance/late-request',
                                        { reason: lateReason },
                                        {
                                            preserveScroll: true,
                                            onSuccess: () => {
                                                window.sessionStorage.setItem(
                                                    promptKeys.clockIn,
                                                    'done',
                                                );
                                                setClockInOpen(false);
                                                setLateReason('');
                                            },
                                        },
                                    )
                                }
                            >
                                Send To Manager
                            </Button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            <Dialog
                open={clockOutOpen}
                onOpenChange={(open) => {
                    setClockOutOpen(open);

                    if (!open) {
                        window.sessionStorage.setItem(
                            promptKeys.clockOut,
                            'dismissed',
                        );
                    }
                }}
            >
                <DialogContent className="sm:max-w-[520px]">
                    <DialogHeader>
                        <DialogTitle>Clock Out Reminder</DialogTitle>
                        <DialogDescription>
                            Your end-of-day clock-out window is approaching.
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-3 text-sm text-muted-foreground">
                        <p>
                            If you confirm from this prompt, your clock-out time
                            will be recorded as {prompt.auto_clock_out_time}{' '}
                            SAST.
                        </p>
                        <p>
                            Prompt opened at {prompt.clock_out_prompt_at} SAST.
                        </p>
                    </div>

                    <DialogFooter className="gap-2">
                        <Button
                            variant="outline"
                            onClick={() => {
                                window.sessionStorage.setItem(
                                    promptKeys.clockOut,
                                    'dismissed',
                                );
                                setClockOutOpen(false);
                            }}
                        >
                            Later
                        </Button>
                        <Button
                            onClick={() =>
                                router.post(
                                    '/settings/attendance/clock-out',
                                    { use_default_time: true },
                                    {
                                        preserveScroll: true,
                                        onSuccess: () => {
                                            window.sessionStorage.setItem(
                                                promptKeys.clockOut,
                                                'done',
                                            );
                                            setClockOutOpen(false);
                                        },
                                    },
                                )
                            }
                        >
                            Clock Out
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

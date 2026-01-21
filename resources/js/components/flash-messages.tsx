// resources/js/Components/FlashMessages.tsx
import { usePage } from '@inertiajs/react';
import { useEffect, useState } from 'react';

type FlashShape = Record<string, string | string[] | undefined>;
type Msg = { id: string; type: 'success' | 'error' | 'warning' | 'info'; text: string };

const AUTO_HIDE_MS = 4000; // change this value to adjust timeout

export default function FlashMessages() {
  const page = usePage();
  const flash = (page.props as any).flash as FlashShape | undefined;

  // Flatten flash into an array of messages for independent dismissals
  const [messages, setMessages] = useState<Msg[]>([]);

  // Build messages whenever flash changes
  useEffect(() => {
    if (!flash || Object.keys(flash).length === 0) {
      setMessages([]);
      return;
    }

    const next: Msg[] = [];
    const keys = Object.keys(flash);
    let idCounter = Date.now();

    keys.forEach((key) => {
      const raw = (flash as any)[key];
      const type =
        key === 'success' ? 'success' :
        key === 'error' || key === 'danger' ? 'error' :
        key === 'warning' ? 'warning' :
        key === 'info' ? 'info' :
        key === 'message' || key === 'status' ? 'info' :
        'info';

      if (!raw) return;
      if (Array.isArray(raw)) {
        raw.forEach((r) => next.push({ id: `${++idCounter}`, type, text: String(r) }));
      } else {
        next.push({ id: `${++idCounter}`, type, text: String(raw) });
      }
    });

    setMessages(next);
  // stringify to avoid deep equality issues while still reacting to changes
  }, [JSON.stringify((page.props as any).flash || {})]);

  // Auto-hide all messages after AUTO_HIDE_MS (resets when messages change)
  useEffect(() => {
    if (messages.length === 0) return;
    const timer = window.setTimeout(() => {
      setMessages([]);
    }, AUTO_HIDE_MS);

    return () => clearTimeout(timer);
  }, [messages]);

  // Manual dismiss (removes single message)
  const dismissOne = (id: string) => {
    setMessages((cur) => cur.filter((m) => m.id !== id));
  };

  if (!messages || messages.length === 0) return null;

  return (
    <div className="fixed top-4 right-4 z-50 flex flex-col gap-3 max-w-sm">
      {messages.map((m) => (
        <div
          key={m.id}
          role="status"
          aria-live="polite"
          className={`w-full p-3 rounded-lg shadow-md flex items-start gap-3 border break-words
            ${m.type === 'success' ? 'bg-green-600 text-white border-green-700' : ''}
            ${m.type === 'error' ? 'bg-red-600 text-white border-red-700' : ''}
            ${m.type === 'info' ? 'bg-blue-600 text-white border-blue-700' : ''}
            ${m.type === 'warning' ? 'bg-yellow-500 text-black border-yellow-600' : ''}
          `}
        >
          <div className="flex-1">
            <div className="text-sm font-medium capitalize">
              {m.type === 'success' ? 'Success' : m.type === 'error' ? 'Error' : m.type}
            </div>
            <div className="text-sm mt-1">{m.text}</div>
          </div>

          <button
            onClick={() => dismissOne(m.id)}
            className="ml-2 text-white/80 hover:text-white"
            aria-label="Dismiss message"
          >
            ✕
          </button>
        </div>
      ))}
    </div>
  );
}

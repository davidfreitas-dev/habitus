import { beforeEach, describe, expect, it, vi } from 'vitest';
import { Preferences } from '@capacitor/preferences';
import { syncQueueService, SYNC_QUEUE_KEY } from '@/services/syncQueueService';

vi.mock('@capacitor/preferences', () => ({
  Preferences: {
    get: vi.fn(),
    set: vi.fn(),
    remove: vi.fn(),
  },
}));

describe('syncQueueService', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('returns empty array when queue does not exist in storage', async () => {
    vi.mocked(Preferences.get).mockResolvedValue({ value: null });

    const queue = await syncQueueService.getQueue();

    expect(queue).toEqual([]);
    expect(Preferences.get).toHaveBeenCalledWith({ key: SYNC_QUEUE_KEY });
  });

  it('parses and returns stored queue items', async () => {
    const mockItems = [
      { id: '1', type: 'TOGGLE_HABIT', habitId: 10, date: '2026-11-18', timestamp: 123456 },
    ];
    vi.mocked(Preferences.get).mockResolvedValue({ value: JSON.stringify(mockItems) });

    const queue = await syncQueueService.getQueue();

    expect(queue).toEqual(mockItems);
  });

  it('enqueues a new toggle action when not in queue', async () => {
    vi.mocked(Preferences.get).mockResolvedValue({ value: null });

    const result = await syncQueueService.enqueueToggle(10, '2026-11-18');

    expect(result.enqueued).toBe(true);
    expect(result.queue).toHaveLength(1);
    expect(result.queue[0]).toMatchObject({
      type: 'TOGGLE_HABIT',
      habitId: 10,
      date: '2026-11-18',
    });
    expect(Preferences.set).toHaveBeenCalledWith({
      key: SYNC_QUEUE_KEY,
      value: expect.stringContaining('"habitId":10'),
    });
  });

  it('coalesces and cancels out when same habit and date is toggled again offline', async () => {
    const existing = [
      { id: 'item_1', type: 'TOGGLE_HABIT', habitId: 10, date: '2026-11-18', timestamp: 1000 },
    ];
    vi.mocked(Preferences.get).mockResolvedValue({ value: JSON.stringify(existing) });

    const result = await syncQueueService.enqueueToggle(10, '2026-11-18');

    expect(result.enqueued).toBe(false);
    expect(result.queue).toHaveLength(0);
    expect(Preferences.remove).toHaveBeenCalledWith({ key: SYNC_QUEUE_KEY });
  });

  it('removes an item by id and updates storage', async () => {
    const existing = [
      { id: 'item_1', type: 'TOGGLE_HABIT', habitId: 10, date: '2026-11-18', timestamp: 1000 },
      { id: 'item_2', type: 'TOGGLE_HABIT', habitId: 20, date: '2026-11-18', timestamp: 1001 },
    ];
    vi.mocked(Preferences.get).mockResolvedValue({ value: JSON.stringify(existing) });

    const updated = await syncQueueService.remove('item_1');

    expect(updated).toHaveLength(1);
    expect(updated[0].id).toBe('item_2');
    expect(Preferences.set).toHaveBeenCalledWith({
      key: SYNC_QUEUE_KEY,
      value: expect.stringContaining('"item_2"'),
    });
  });

  it('clears the entire queue', async () => {
    await syncQueueService.clear();

    expect(Preferences.remove).toHaveBeenCalledWith({ key: SYNC_QUEUE_KEY });
  });
});

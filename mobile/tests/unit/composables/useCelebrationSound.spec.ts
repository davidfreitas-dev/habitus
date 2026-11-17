import { describe, expect, it, vi, beforeEach } from 'vitest';
import { useCelebrationSound } from '@/composables/useCelebrationSound';

describe('useCelebrationSound', () => {
  beforeEach(() => {
    vi.restoreAllMocks();
  });

  it('handles environment gracefully when AudioContext is not supported', () => {
    const originalAudioContext = window.AudioContext;
    // @ts-expect-error simulação de browser sem suporte
    delete window.AudioContext;

    const { playCelebrationSound } = useCelebrationSound();
    expect(() => playCelebrationSound()).not.toThrow();

    window.AudioContext = originalAudioContext;
  });

  it('creates oscillators and gain nodes when AudioContext is available', () => {
    const mockOscillator = {
      type: '',
      frequency: { setValueAtTime: vi.fn() },
      connect: vi.fn(),
      start: vi.fn(),
      stop: vi.fn(),
    };

    const mockGain = {
      gain: {
        setValueAtTime: vi.fn(),
        linearRampToValueAtTime: vi.fn(),
        exponentialRampToValueAtTime: vi.fn(),
      },
      connect: vi.fn(),
    };

    const mockContext = {
      state: 'running',
      currentTime: 10,
      destination: {},
      createOscillator: vi.fn(() => mockOscillator),
      createGain: vi.fn(() => mockGain),
      resume: vi.fn(),
    };

    // @ts-expect-error mock para teste
    window.AudioContext = vi.fn(() => mockContext);

    const { playCelebrationSound } = useCelebrationSound();
    playCelebrationSound();

    expect(mockContext.createOscillator).toHaveBeenCalledTimes(4);
    expect(mockContext.createGain).toHaveBeenCalledTimes(4);
    expect(mockOscillator.start).toHaveBeenCalledTimes(4);
    expect(mockOscillator.stop).toHaveBeenCalledTimes(4);
  });
});

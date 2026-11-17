import { describe, expect, it } from 'vitest';
import { resolveDeepLinkRoute } from '@/composables/useDeepLinks';

describe('resolveDeepLinkRoute', () => {
  it('resolves HTTPS Universal Link for email verification', () => {
    const route = resolveDeepLinkRoute('https://habits.davidfreitas.dev.br/verify-email?token=abc123xyz');
    expect(route).toBe('/verify-email?token=abc123xyz');
  });

  it('resolves custom scheme habits:// for email verification', () => {
    const route = resolveDeepLinkRoute('habits://verify-email?token=custom999');
    expect(route).toBe('/verify-email?token=custom999');
  });

  it('resolves password reset deep link', () => {
    const route = resolveDeepLinkRoute('https://habits.davidfreitas.dev.br/forgot/reset?data=xyz');
    expect(route).toBe('/forgot/reset?data=xyz');
  });

  it('returns null for unrecognized paths or invalid URLs', () => {
    expect(resolveDeepLinkRoute('https://habits.davidfreitas.dev.br/unknown')).toBeNull();
    expect(resolveDeepLinkRoute('not-a-valid-url')).toBeNull();
  });
});

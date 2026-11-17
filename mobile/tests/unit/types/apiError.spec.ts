import { describe, expect, it } from 'vitest';
import { getErrorMessage } from '@/types/api';

describe('getErrorMessage', () => {
  it('extracts error message from response.data.message', () => {
    const error = {
      response: {
        data: {
          message: 'E-mail ou senha incorretos.',
        },
      },
    };

    expect(getErrorMessage(error)).toBe('E-mail ou senha incorretos.');
  });

  it('extracts first error from response.data.data array', () => {
    const error = {
      response: {
        data: {
          data: ['O título é obrigatório.', 'Selecione os dias.'],
        },
      },
    };

    expect(getErrorMessage(error)).toBe('O título é obrigatório.');
  });

  it('falls back to err.message when response data is absent', () => {
    const error = new Error('Falha na conexão');
    expect(getErrorMessage(error)).toBe('Falha na conexão');
  });

  it('returns fallback message when error structure is completely unrecognized', () => {
    expect(getErrorMessage(null, 'Erro customizado')).toBe('Erro customizado');
    expect(getErrorMessage(undefined)).toBe('Ocorreu um erro inesperado.');
    expect(getErrorMessage('string error')).toBe('Ocorreu um erro inesperado.');
  });
});

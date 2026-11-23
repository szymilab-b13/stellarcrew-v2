import { useMutation } from '@tanstack/react-query';

import { createPairingCode, type CreatePairingCodeInput } from './workersApi';
import type { PairingCodeResponse } from '../types';

export function useCreatePairingCode() {
  return useMutation<PairingCodeResponse, Error, CreatePairingCodeInput>({
    mutationFn: (input) => createPairingCode(input),
  });
}

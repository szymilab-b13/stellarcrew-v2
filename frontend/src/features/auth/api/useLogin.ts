import { useMutation, useQueryClient } from '@tanstack/react-query';

import { login } from './authApi';
import { authMeQueryKey } from './useMe';

export function useLogin() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: login,
    onSuccess: ({ user }) => {
      queryClient.setQueryData(authMeQueryKey, user);
    },
  });
}

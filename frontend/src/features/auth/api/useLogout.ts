import { useMutation, useQueryClient } from '@tanstack/react-query';

import { logout } from './authApi';
import { authMeQueryKey } from './useMe';

export function useLogout() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: logout,
    onSettled: () => {
      queryClient.removeQueries({ queryKey: authMeQueryKey });
    },
  });
}

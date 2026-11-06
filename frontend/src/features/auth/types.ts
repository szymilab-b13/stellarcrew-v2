export interface AuthUser {
  email: string;
  displayName: string | null;
  role: string;
}

export interface LoginPayload {
  email: string;
  password: string;
}

export interface LoginResponse {
  token: string;
  user: AuthUser;
}

interface AuthUserPayload {
  email: string;
  display_name: string | null;
  role: string;
}

interface LoginApiPayload {
  token: string;
  user: AuthUserPayload;
}

export function mapAuthUser(payload: AuthUserPayload): AuthUser {
  return {
    email: payload.email,
    displayName: payload.display_name,
    role: payload.role,
  };
}

export function mapLoginResponse(payload: LoginApiPayload): LoginResponse {
  return {
    token: payload.token,
    user: mapAuthUser(payload.user),
  };
}

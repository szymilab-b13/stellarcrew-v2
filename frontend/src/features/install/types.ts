export type InstallStepKey = 'admin' | 'branding' | 'logo' | 'workspace' | 'review';

export interface InstallStepDescriptor {
  key: InstallStepKey;
  label: string;
  short: string;
}

export const INSTALL_STEPS: readonly InstallStepDescriptor[] = [
  { key: 'admin', label: 'Admin & profile', short: 'Admin' },
  { key: 'branding', label: 'Branding', short: 'Branding' },
  { key: 'logo', label: 'Logo', short: 'Logo' },
  { key: 'workspace', label: 'First workspace', short: 'Workspace' },
  { key: 'review', label: 'Review', short: 'Review' },
];

export interface AdminProfileState {
  email: string;
  password: string;
  passwordConfirm: string;
  displayName: string;
}

export interface BrandingState {
  instanceName: string;
  locale: string;
}

export interface LogoState {
  file: File | null;
  previewUrl: string | null;
}

export interface WorkspaceState {
  name: string;
}

export interface InstallFormState {
  admin: AdminProfileState;
  branding: BrandingState;
  logo: LogoState;
  workspace: WorkspaceState;
  selectedAgentSlugs: string[];
}

export const EMPTY_INSTALL_FORM: InstallFormState = {
  admin: { email: '', password: '', passwordConfirm: '', displayName: '' },
  branding: { instanceName: '', locale: 'en' },
  logo: { file: null, previewUrl: null },
  workspace: { name: '' },
  selectedAgentSlugs: [],
};

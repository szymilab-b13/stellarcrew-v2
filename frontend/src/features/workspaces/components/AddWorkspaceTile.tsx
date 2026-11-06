import { Plus } from 'lucide-react';
import { useTranslation } from 'react-i18next';

interface AddWorkspaceTileProps {
  onClick: () => void;
}

export function AddWorkspaceTile({ onClick }: AddWorkspaceTileProps) {
  const { t } = useTranslation();

  return (
    <button
      type="button"
      onClick={onClick}
      className="flex min-h-[8rem] flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-sc-border bg-sc-bg-card/40 p-4 text-sc-text-muted transition-colors hover:border-sc-text-muted hover:text-sc-text"
    >
      <Plus size={24} aria-hidden />
      <span className="text-sm font-semibold">{t('workspaces.addTile.label')}</span>
    </button>
  );
}

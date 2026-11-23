export const ROLES = {
  SUPER_ADMIN: 'SUPER_ADMIN',
  ADMIN_SATKER: 'ADMIN_SATKER',
  KASATKER: 'KASATKER',
  KABID: 'KABID',
  KASUB: 'KASUB',
  STAFF: 'STAFF',
} as const;

export type RoleCode = keyof typeof ROLES;

export const ROLE_LABELS: Record<string, string> = {
  SUPER_ADMIN: 'Super Admin',
  ADMIN_SATKER: 'Admin Satker',
  KASATKER: 'Kepala Satuan Kerja',
  KABID: 'Kepala Bidang',
  KASUB: 'Kepala Subbidang',
  STAFF: 'Staff',
};

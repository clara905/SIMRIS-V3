export type JenisUnit =
  | 'SATKER'
  | 'BIDANG'
  | 'BAGIAN'
  | 'SUBBIDANG'
  | 'SUBBAGIAN';

export interface UnitOrganisasi {
  id: number;
  parent_id: number | null;
  kode: string;
  nama: string;
  jenis: JenisUnit;
  aktif: boolean;
}

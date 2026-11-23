/**
 * Risk Treatment Service
 * 
 * ERD-Compliant Services for Risk Treatment (Penanganan Risiko)
 * Following SIMRIS Consolidated ERD entities:
 * - RENCANA_PENANGANAN (Treatment Plan)
 * - PROGRES_PENANGANAN (Treatment Progress)
 * - BUKTI_PENANGANAN (Treatment Evidence)
 * - RISIKO_KONTROL (Risk-Control Relationship)
 * - KONTROL_KEAMANAN (Security Control)
 * - PENANGANAN_SOP (Treatment-SOP Relationship)
 * - SOP (Standard Operating Procedure)
 */

import type {
  RencanaPenanganan,
  ProgresPenanganan,
  BuktiPenanganan,
  RisikoKontrol,
  KontrolKeamanan,
  PenangananSOP,
  SOP,
  Dokumen
} from '../types/risk.types';

// ============================================================================
// RENCANA_PENANGANAN Service
// ============================================================================

export const rencanaPenangananService = {
  /**
   * Get all treatment plans for current STAFF's Satker
   */
  async getAll(): Promise<RencanaPenanganan[]> {
    // Mock data - akan diganti dengan API call ke Laravel backend
    // GET /api/staff/rencana-penanganan
    return new Promise((resolve) => {
      setTimeout(() => {
        resolve(MOCK_RENCANA_PENANGANAN);
      }, 300);
    });
  },

  /**
   * Get treatment plan by ID
   */
  async getById(id: string): Promise<RencanaPenanganan | null> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const plan = MOCK_RENCANA_PENANGANAN.find(p => p.id_rencana === id);
        resolve(plan || null);
      }, 200);
    });
  },

  /**
   * Get treatment plans for specific risk
   */
  async getByRisiko(id_risiko: string): Promise<RencanaPenanganan[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const plans = MOCK_RENCANA_PENANGANAN.filter(p => p.id_risiko === id_risiko);
        resolve(plans);
      }, 200);
    });
  },

  /**
   * Create new treatment plan
   */
  async create(data: Omit<RencanaPenanganan, 'id_rencana' | 'tgl_buat' | 'pembuat'>): Promise<RencanaPenanganan> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const newPlan: RencanaPenanganan = {
          id_rencana: `RP${Date.now()}`,
          tgl_buat: new Date().toISOString(),
          pembuat: 'STAFF001',
          ...data
        };
        MOCK_RENCANA_PENANGANAN.push(newPlan);
        resolve(newPlan);
      }, 300);
    });
  },

  /**
   * Update treatment plan
   */
  async update(id: string, data: Partial<RencanaPenanganan>): Promise<RencanaPenanganan> {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        const index = MOCK_RENCANA_PENANGANAN.findIndex(p => p.id_rencana === id);
        if (index === -1) {
          reject(new Error('Treatment plan not found'));
          return;
        }
        MOCK_RENCANA_PENANGANAN[index] = {
          ...MOCK_RENCANA_PENANGANAN[index],
          ...data
        };
        resolve(MOCK_RENCANA_PENANGANAN[index]);
      }, 300);
    });
  },

  /**
   * Delete treatment plan
   */
  async delete(id: string): Promise<void> {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        const index = MOCK_RENCANA_PENANGANAN.findIndex(p => p.id_rencana === id);
        if (index === -1) {
          reject(new Error('Treatment plan not found'));
          return;
        }
        MOCK_RENCANA_PENANGANAN.splice(index, 1);
        resolve();
      }, 300);
    });
  },

  /**
   * Get treatment plan summary statistics
   */
  async getSummary(): Promise<{
    total: number;
    belum_mulai: number;
    sedang_berjalan: number;
    selesai: number;
    terlambat: number;
  }> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const plans = MOCK_RENCANA_PENANGANAN;
        const now = new Date();
        
        const summary = {
          total: plans.length,
          belum_mulai: plans.filter(p => p.status === 'belum_mulai').length,
          sedang_berjalan: plans.filter(p => p.status === 'sedang_berjalan').length,
          selesai: plans.filter(p => p.status === 'selesai').length,
          terlambat: plans.filter(p => {
            if (p.status === 'selesai') return false;
            return new Date(p.tgl_target) < now;
          }).length
        };
        
        resolve(summary);
      }, 200);
    });
  }
};

// ============================================================================
// PROGRES_PENANGANAN Service
// ============================================================================

export const progresService = {
  /**
   * Get all progress entries for a treatment plan
   */
  async getByRencana(id_rencana: string): Promise<ProgresPenanganan[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const progress = MOCK_PROGRES_PENANGANAN.filter(p => p.id_rencana === id_rencana);
        resolve(progress);
      }, 200);
    });
  },

  /**
   * Get progress entry by ID
   */
  async getById(id: string): Promise<ProgresPenanganan | null> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const progress = MOCK_PROGRES_PENANGANAN.find(p => p.id_progres === id);
        resolve(progress || null);
      }, 200);
    });
  },

  /**
   * Create new progress entry
   */
  async create(data: Omit<ProgresPenanganan, 'id_progres'>): Promise<ProgresPenanganan> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const newProgress: ProgresPenanganan = {
          id_progres: `PG${Date.now()}`,
          ...data
        };
        MOCK_PROGRES_PENANGANAN.push(newProgress);
        resolve(newProgress);
      }, 300);
    });
  },

  /**
   * Update progress entry
   */
  async update(id: string, data: Partial<ProgresPenanganan>): Promise<ProgresPenanganan> {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        const index = MOCK_PROGRES_PENANGANAN.findIndex(p => p.id_progres === id);
        if (index === -1) {
          reject(new Error('Progress entry not found'));
          return;
        }
        MOCK_PROGRES_PENANGANAN[index] = {
          ...MOCK_PROGRES_PENANGANAN[index],
          ...data
        };
        resolve(MOCK_PROGRES_PENANGANAN[index]);
      }, 300);
    });
  },

  /**
   * Delete progress entry
   */
  async delete(id: string): Promise<void> {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        const index = MOCK_PROGRES_PENANGANAN.findIndex(p => p.id_progres === id);
        if (index === -1) {
          reject(new Error('Progress entry not found'));
          return;
        }
        MOCK_PROGRES_PENANGANAN.splice(index, 1);
        resolve();
      }, 300);
    });
  }
};

// ============================================================================
// BUKTI_PENANGANAN Service
// ============================================================================

export const buktiService = {
  /**
   * Get all evidence for a progress entry
   */
  async getByProgres(id_progres: string): Promise<BuktiPenanganan[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const evidence = MOCK_BUKTI_PENANGANAN.filter(b => b.id_progres === id_progres);
        resolve(evidence);
      }, 200);
    });
  },

  /**
   * Get evidence by ID
   */
  async getById(id: string): Promise<BuktiPenanganan | null> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const evidence = MOCK_BUKTI_PENANGANAN.find(b => b.id_bukti === id);
        resolve(evidence || null);
      }, 200);
    });
  },

  /**
   * Upload new evidence
   */
  async upload(data: Omit<BuktiPenanganan, 'id_bukti' | 'tgl_upload' | 'uploader'>): Promise<BuktiPenanganan> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const newEvidence: BuktiPenanganan = {
          id_bukti: `BK${Date.now()}`,
          tgl_upload: new Date().toISOString(),
          uploader: 'STAFF001',
          ...data
        };
        MOCK_BUKTI_PENANGANAN.push(newEvidence);
        resolve(newEvidence);
      }, 500);
    });
  },

  /**
   * Delete evidence
   */
  async delete(id: string): Promise<void> {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        const index = MOCK_BUKTI_PENANGANAN.findIndex(b => b.id_bukti === id);
        if (index === -1) {
          reject(new Error('Evidence not found'));
          return;
        }
        MOCK_BUKTI_PENANGANAN.splice(index, 1);
        resolve();
      }, 300);
    });
  }
};

// ============================================================================
// KONTROL_KEAMANAN & RISIKO_KONTROL Service
// ============================================================================

export const kontrolService = {
  /**
   * Get all available security controls
   */
  async getAll(): Promise<KontrolKeamanan[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        resolve(MOCK_KONTROL_KEAMANAN);
      }, 200);
    });
  },

  /**
   * Get control by ID
   */
  async getById(id: string): Promise<KontrolKeamanan | null> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const control = MOCK_KONTROL_KEAMANAN.find(k => k.id_kontrol === id);
        resolve(control || null);
      }, 200);
    });
  },

  /**
   * Get controls assigned to a risk
   */
  async getByRisiko(id_risiko: string): Promise<RisikoKontrol[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const assignments = MOCK_RISIKO_KONTROL.filter(rk => rk.id_risiko === id_risiko);
        resolve(assignments);
      }, 200);
    });
  },

  /**
   * Assign control to risk
   */
  async assignToRisiko(data: Omit<RisikoKontrol, 'id_risiko_kontrol' | 'tgl_assign' | 'penanggung_jawab'>): Promise<RisikoKontrol> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const newAssignment: RisikoKontrol = {
          id_risiko_kontrol: `RK${Date.now()}`,
          tgl_assign: new Date().toISOString(),
          penanggung_jawab: 'STAFF001',
          ...data
        };
        MOCK_RISIKO_KONTROL.push(newAssignment);
        resolve(newAssignment);
      }, 300);
    });
  },

  /**
   * Update control assignment
   */
  async updateAssignment(id: string, data: Partial<RisikoKontrol>): Promise<RisikoKontrol> {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        const index = MOCK_RISIKO_KONTROL.findIndex(rk => rk.id_risiko_kontrol === id);
        if (index === -1) {
          reject(new Error('Control assignment not found'));
          return;
        }
        MOCK_RISIKO_KONTROL[index] = {
          ...MOCK_RISIKO_KONTROL[index],
          ...data
        };
        resolve(MOCK_RISIKO_KONTROL[index]);
      }, 300);
    });
  },

  /**
   * Remove control from risk
   */
  async removeFromRisiko(id: string): Promise<void> {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        const index = MOCK_RISIKO_KONTROL.findIndex(rk => rk.id_risiko_kontrol === id);
        if (index === -1) {
          reject(new Error('Control assignment not found'));
          return;
        }
        MOCK_RISIKO_KONTROL.splice(index, 1);
        resolve();
      }, 300);
    });
  }
};

// ============================================================================
// SOP & PENANGANAN_SOP Service
// ============================================================================

export const sopService = {
  /**
   * Get all available SOPs for current Satker
   */
  async getAll(): Promise<SOP[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        resolve(MOCK_SOP);
      }, 200);
    });
  },

  /**
   * Get SOP by ID
   */
  async getById(id: string): Promise<SOP | null> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const sop = MOCK_SOP.find(s => s.id_sop === id);
        resolve(sop || null);
      }, 200);
    });
  },

  /**
   * Get SOPs linked to a treatment plan
   */
  async getByRencana(id_rencana: string): Promise<PenangananSOP[]> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const links = MOCK_PENANGANAN_SOP.filter(ps => ps.id_rencana === id_rencana);
        resolve(links);
      }, 200);
    });
  },

  /**
   * Link SOP to treatment plan
   */
  async linkToRencana(data: Omit<PenangananSOP, 'id_penanganan_sop' | 'tgl_link'>): Promise<PenangananSOP> {
    return new Promise((resolve) => {
      setTimeout(() => {
        const newLink: PenangananSOP = {
          id_penanganan_sop: `PS${Date.now()}`,
          tgl_link: new Date().toISOString(),
          ...data
        };
        MOCK_PENANGANAN_SOP.push(newLink);
        resolve(newLink);
      }, 300);
    });
  },

  /**
   * Update SOP link
   */
  async updateLink(id: string, data: Partial<PenangananSOP>): Promise<PenangananSOP> {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        const index = MOCK_PENANGANAN_SOP.findIndex(ps => ps.id_penanganan_sop === id);
        if (index === -1) {
          reject(new Error('SOP link not found'));
          return;
        }
        MOCK_PENANGANAN_SOP[index] = {
          ...MOCK_PENANGANAN_SOP[index],
          ...data
        };
        resolve(MOCK_PENANGANAN_SOP[index]);
      }, 300);
    });
  },

  /**
   * Unlink SOP from treatment plan
   */
  async unlinkFromRencana(id: string): Promise<void> {
    return new Promise((resolve, reject) => {
      setTimeout(() => {
        const index = MOCK_PENANGANAN_SOP.findIndex(ps => ps.id_penanganan_sop === id);
        if (index === -1) {
          reject(new Error('SOP link not found'));
          return;
        }
        MOCK_PENANGANAN_SOP.splice(index, 1);
        resolve();
      }, 300);
    });
  }
};

// ============================================================================
// MOCK DATA - CLEARLY LABELED
// ============================================================================

const MOCK_RENCANA_PENANGANAN: RencanaPenanganan[] = [
  {
    id_rencana: 'RP001',
    id_risiko: 'R001', // Kebocoran data karyawan
    strategi: 'mitigasi',
    deskripsi: 'Implementasi enkripsi database dan access control yang ketat',
    tgl_mulai: '2024-01-15',
    tgl_target: '2024-03-15',
    pic: 'STAFF001',
    anggaran: 50000000,
    status: 'sedang_berjalan',
    tgl_buat: '2024-01-10T08:00:00Z',
    pembuat: 'STAFF001',
    catatan: 'Prioritas tinggi - data sensitif karyawan'
  },
  {
    id_rencana: 'RP002',
    id_risiko: 'R001', // Kebocoran data karyawan - strategi kedua
    strategi: 'mitigasi',
    deskripsi: 'Pelatihan awareness keamanan data untuk seluruh staff',
    tgl_mulai: '2024-02-01',
    tgl_target: '2024-02-28',
    pic: 'STAFF002',
    anggaran: 15000000,
    status: 'selesai',
    tgl_buat: '2024-01-20T10:30:00Z',
    pembuat: 'STAFF001',
    catatan: 'Training selesai dilaksanakan 25 Feb 2024'
  },
  {
    id_rencana: 'RP003',
    id_risiko: 'R002', // Kerusakan server
    strategi: 'transfer',
    deskripsi: 'Asuransi perangkat IT dan pembelian extended warranty',
    tgl_mulai: '2024-01-20',
    tgl_target: '2024-02-20',
    pic: 'STAFF003',
    anggaran: 25000000,
    status: 'sedang_berjalan',
    tgl_buat: '2024-01-15T14:00:00Z',
    pembuat: 'STAFF001',
    catatan: null
  },
  {
    id_rencana: 'RP004',
    id_risiko: 'R003', // Serangan ransomware
    strategi: 'mitigasi',
    deskripsi: 'Implementasi backup otomatis harian dengan offline storage',
    tgl_mulai: '2024-02-10',
    tgl_target: '2024-04-10',
    pic: 'STAFF001',
    anggaran: 75000000,
    status: 'belum_mulai',
    tgl_buat: '2024-02-05T09:15:00Z',
    pembuat: 'STAFF001',
    catatan: 'Menunggu approval anggaran dari Kasatker'
  },
  {
    id_rencana: 'RP005',
    id_risiko: 'R003', // Serangan ransomware - strategi kedua
    strategi: 'mitigasi',
    deskripsi: 'Deploy next-generation firewall dan intrusion detection system',
    tgl_mulai: '2024-03-01',
    tgl_target: '2024-05-31',
    pic: 'STAFF004',
    anggaran: 150000000,
    status: 'belum_mulai',
    tgl_buat: '2024-02-20T11:00:00Z',
    pembuat: 'STAFF001',
    catatan: 'Koordinasi dengan vendor untuk procurement'
  }
];

const MOCK_PROGRES_PENANGANAN: ProgresPenanganan[] = [
  {
    id_progres: 'PG001',
    id_rencana: 'RP001',
    tgl_progres: '2024-01-20T10:00:00Z',
    persen_selesai: 25,
    deskripsi: 'Analisis requirement enkripsi database selesai. Memilih algoritma AES-256.',
    status_kendala: null,
    deskripsi_kendala: null,
    tgl_kendala_selesai: null,
    catatan: 'Tim sedang review algoritma enkripsi'
  },
  {
    id_progres: 'PG002',
    id_rencana: 'RP001',
    tgl_progres: '2024-02-05T14:30:00Z',
    persen_selesai: 50,
    deskripsi: 'Implementasi enkripsi pada tabel karyawan dan penggajian selesai',
    status_kendala: null,
    deskripsi_kendala: null,
    tgl_kendala_selesai: null,
    catatan: 'Testing enkripsi berjalan lancar'
  },
  {
    id_progres: 'PG003',
    id_rencana: 'RP001',
    tgl_progres: '2024-02-20T09:00:00Z',
    persen_selesai: 65,
    deskripsi: 'Setup access control dan role-based permissions',
    status_kendala: 'terjadi',
    deskripsi_kendala: 'Konflik permission dengan sistem legacy',
    tgl_kendala_selesai: null,
    catatan: 'Memerlukan koordinasi dengan vendor sistem lama'
  },
  {
    id_progres: 'PG004',
    id_rencana: 'RP002',
    tgl_progres: '2024-02-15T13:00:00Z',
    persen_selesai: 100,
    deskripsi: 'Training awareness keamanan data selesai diikuti 45 staff',
    status_kendala: null,
    deskripsi_kendala: null,
    tgl_kendala_selesai: null,
    catatan: 'Semua peserta lulus post-test dengan nilai minimal 80'
  },
  {
    id_progres: 'PG005',
    id_rencana: 'RP003',
    tgl_progres: '2024-02-01T11:00:00Z',
    persen_selesai: 40,
    deskripsi: 'Proposal asuransi disetujui, menunggu dokumen kontrak',
    status_kendala: null,
    deskripsi_kendala: null,
    tgl_kendala_selesai: null,
    catatan: 'Coverage: Rp 500 juta per kejadian'
  }
];

const MOCK_BUKTI_PENANGANAN: BuktiPenanganan[] = [
  {
    id_bukti: 'BK001',
    id_progres: 'PG001',
    id_dokumen: 'DOK001',
    tgl_upload: '2024-01-20T10:30:00Z',
    uploader: 'STAFF001',
    keterangan: 'Dokumen analisis requirement enkripsi database'
  },
  {
    id_bukti: 'BK002',
    id_progres: 'PG002',
    id_dokumen: 'DOK002',
    tgl_upload: '2024-02-05T15:00:00Z',
    uploader: 'STAFF001',
    keterangan: 'Screenshot hasil testing enkripsi database'
  },
  {
    id_bukti: 'BK003',
    id_progres: 'PG002',
    id_dokumen: 'DOK003',
    tgl_upload: '2024-02-05T15:15:00Z',
    uploader: 'STAFF001',
    keterangan: 'Laporan hasil penetration testing enkripsi'
  },
  {
    id_bukti: 'BK004',
    id_progres: 'PG004',
    id_dokumen: 'DOK004',
    tgl_upload: '2024-02-15T14:00:00Z',
    uploader: 'STAFF002',
    keterangan: 'Daftar hadir training awareness keamanan data'
  },
  {
    id_bukti: 'BK005',
    id_progres: 'PG004',
    id_dokumen: 'DOK005',
    tgl_upload: '2024-02-15T14:30:00Z',
    uploader: 'STAFF002',
    keterangan: 'Hasil post-test training keamanan data'
  },
  {
    id_bukti: 'BK006',
    id_progres: 'PG005',
    id_dokumen: 'DOK006',
    tgl_upload: '2024-02-01T12:00:00Z',
    uploader: 'STAFF003',
    keterangan: 'Polis asuransi perangkat IT (draft)'
  }
];

const MOCK_KONTROL_KEAMANAN: KontrolKeamanan[] = [
  {
    id_kontrol: 'K001',
    kode_kontrol: 'AC-01',
    nama_kontrol: 'Access Control Policy',
    kategori: 'administrative',
    deskripsi: 'Kebijakan kontrol akses yang mendefinisikan hak akses pengguna',
    referensi_standard: 'ISO 27001:2013 - A.9.1',
    status_aktif: true
  },
  {
    id_kontrol: 'K002',
    kode_kontrol: 'CR-01',
    nama_kontrol: 'Database Encryption',
    kategori: 'technical',
    deskripsi: 'Enkripsi data sensitif dalam database menggunakan AES-256',
    referensi_standard: 'ISO 27001:2013 - A.10.1',
    status_aktif: true
  },
  {
    id_kontrol: 'K003',
    kode_kontrol: 'AU-01',
    nama_kontrol: 'Audit Logging',
    kategori: 'technical',
    deskripsi: 'Logging semua aktivitas akses ke data sensitif',
    referensi_standard: 'ISO 27001:2013 - A.12.4',
    status_aktif: true
  },
  {
    id_kontrol: 'K004',
    kode_kontrol: 'BC-01',
    nama_kontrol: 'Automated Backup',
    kategori: 'technical',
    deskripsi: 'Backup otomatis harian dengan retention 30 hari',
    referensi_standard: 'ISO 27001:2013 - A.12.3',
    status_aktif: true
  },
  {
    id_kontrol: 'K005',
    kode_kontrol: 'FW-01',
    nama_kontrol: 'Next-Gen Firewall',
    kategori: 'physical',
    deskripsi: 'Firewall dengan deep packet inspection dan IPS',
    referensi_standard: 'ISO 27001:2013 - A.13.1',
    status_aktif: true
  },
  {
    id_kontrol: 'K006',
    kode_kontrol: 'TR-01',
    nama_kontrol: 'Security Awareness Training',
    kategori: 'administrative',
    deskripsi: 'Pelatihan berkala kesadaran keamanan informasi',
    referensi_standard: 'ISO 27001:2013 - A.7.2',
    status_aktif: true
  }
];

const MOCK_RISIKO_KONTROL: RisikoKontrol[] = [
  {
    id_risiko_kontrol: 'RK001',
    id_risiko: 'R001',
    id_kontrol: 'K001',
    tgl_assign: '2024-01-15T08:00:00Z',
    status_implementasi: 'implemented',
    efektivitas: 'high',
    tgl_review: '2024-02-15T10:00:00Z',
    hasil_review: 'Kontrol berjalan efektif, tidak ada pelanggaran akses',
    penanggung_jawab: 'STAFF001',
    catatan: null
  },
  {
    id_risiko_kontrol: 'RK002',
    id_risiko: 'R001',
    id_kontrol: 'K002',
    tgl_assign: '2024-01-15T08:30:00Z',
    status_implementasi: 'in_progress',
    efektivitas: null,
    tgl_review: null,
    hasil_review: null,
    penanggung_jawab: 'STAFF001',
    catatan: 'Sedang implementasi enkripsi database'
  },
  {
    id_risiko_kontrol: 'RK003',
    id_risiko: 'R001',
    id_kontrol: 'K003',
    tgl_assign: '2024-01-15T09:00:00Z',
    status_implementasi: 'implemented',
    efektivitas: 'medium',
    tgl_review: '2024-02-10T14:00:00Z',
    hasil_review: 'Logging berjalan baik, perlu penambahan alert otomatis',
    penanggung_jawab: 'STAFF001',
    catatan: null
  },
  {
    id_risiko_kontrol: 'RK004',
    id_risiko: 'R003',
    id_kontrol: 'K004',
    tgl_assign: '2024-02-10T10:00:00Z',
    status_implementasi: 'planned',
    efektivitas: null,
    tgl_review: null,
    hasil_review: null,
    penanggung_jawab: 'STAFF001',
    catatan: 'Menunggu procurement storage backup'
  },
  {
    id_risiko_kontrol: 'RK005',
    id_risiko: 'R003',
    id_kontrol: 'K005',
    tgl_assign: '2024-03-01T09:00:00Z',
    status_implementasi: 'planned',
    efektivitas: null,
    tgl_review: null,
    hasil_review: null,
    penanggung_jawab: 'STAFF004',
    catatan: 'Koordinasi vendor untuk deployment firewall'
  },
  {
    id_risiko_kontrol: 'RK006',
    id_risiko: 'R001',
    id_kontrol: 'K006',
    tgl_assign: '2024-02-01T08:00:00Z',
    status_implementasi: 'implemented',
    efektivitas: 'high',
    tgl_review: '2024-02-25T13:00:00Z',
    hasil_review: 'Training selesai, semua peserta lulus',
    penanggung_jawab: 'STAFF002',
    catatan: null
  }
];

const MOCK_SOP: SOP[] = [
  {
    id_sop: 'SOP001',
    kode_sop: 'SOP-IT-001',
    judul: 'Prosedur Backup dan Recovery Data',
    deskripsi: 'Standar prosedur untuk backup rutin dan recovery data dalam kondisi darurat',
    tgl_terbit: '2023-06-01',
    versi: '2.1',
    status: 'aktif',
    id_dokumen: 'DOK101'
  },
  {
    id_sop: 'SOP002',
    kode_sop: 'SOP-IT-002',
    judul: 'Prosedur Manajemen Akses Pengguna',
    deskripsi: 'Panduan pemberian, modifikasi, dan pencabutan hak akses sistem',
    tgl_terbit: '2023-08-15',
    versi: '1.5',
    status: 'aktif',
    id_dokumen: 'DOK102'
  },
  {
    id_sop: 'SOP003',
    kode_sop: 'SOP-IT-003',
    judul: 'Prosedur Incident Response Keamanan',
    deskripsi: 'Langkah-langkah penanganan insiden keamanan informasi',
    tgl_terbit: '2023-09-20',
    versi: '1.0',
    status: 'aktif',
    id_dokumen: 'DOK103'
  },
  {
    id_sop: 'SOP004',
    kode_sop: 'SOP-IT-004',
    judul: 'Prosedur Patch Management',
    deskripsi: 'Standar penerapan patch dan update sistem',
    tgl_terbit: '2023-10-10',
    versi: '1.2',
    status: 'aktif',
    id_dokumen: 'DOK104'
  },
  {
    id_sop: 'SOP005',
    kode_sop: 'SOP-IT-005',
    judul: 'Prosedur Klasifikasi dan Penanganan Data',
    deskripsi: 'Panduan klasifikasi tingkat kerahasiaan data dan prosedur penanganannya',
    tgl_terbit: '2023-11-01',
    versi: '2.0',
    status: 'aktif',
    id_dokumen: 'DOK105'
  }
];

const MOCK_PENANGANAN_SOP: PenangananSOP[] = [
  {
    id_penanganan_sop: 'PS001',
    id_rencana: 'RP001',
    id_sop: 'SOP002',
    tgl_link: '2024-01-15T10:00:00Z',
    relevansi: 'Implementasi enkripsi database mengikuti prosedur manajemen akses',
    status_adopsi: 'diterapkan',
    catatan: null
  },
  {
    id_penanganan_sop: 'PS002',
    id_rencana: 'RP001',
    id_sop: 'SOP005',
    tgl_link: '2024-01-15T10:15:00Z',
    relevansi: 'Enkripsi mengikuti klasifikasi data sesuai SOP',
    status_adopsi: 'diterapkan',
    catatan: null
  },
  {
    id_penanganan_sop: 'PS003',
    id_rencana: 'RP002',
    id_sop: 'SOP005',
    tgl_link: '2024-01-20T11:00:00Z',
    relevansi: 'Training mencakup materi klasifikasi dan penanganan data',
    status_adopsi: 'diterapkan',
    catatan: null
  },
  {
    id_penanganan_sop: 'PS004',
    id_rencana: 'RP004',
    id_sop: 'SOP001',
    tgl_link: '2024-02-10T09:30:00Z',
    relevansi: 'Implementasi backup mengikuti SOP backup dan recovery',
    status_adopsi: 'dalam_review',
    catatan: 'Menunggu approval teknis dari tim IT'
  },
  {
    id_penanganan_sop: 'PS005',
    id_rencana: 'RP004',
    id_sop: 'SOP003',
    tgl_link: '2024-02-10T09:45:00Z',
    relevansi: 'Backup sebagai bagian dari incident response ransomware',
    status_adopsi: 'dalam_review',
    catatan: null
  },
  {
    id_penanganan_sop: 'PS006',
    id_rencana: 'RP005',
    id_sop: 'SOP003',
    tgl_link: '2024-03-01T11:30:00Z',
    relevansi: 'Firewall dan IDS terintegrasi dengan prosedur incident response',
    status_adopsi: 'direncanakan',
    catatan: 'Akan diimplementasikan setelah deployment firewall'
  }
];

// Mock DOKUMEN for reference (would normally come from document service)
export const MOCK_DOKUMEN: Dokumen[] = [
  {
    id_dokumen: 'DOK001',
    nama_file: 'Analisis_Requirement_Enkripsi_v1.pdf',
    tipe_file: 'application/pdf',
    ukuran_file: 2456789,
    path_file: '/documents/penanganan/DOK001.pdf',
    tgl_upload: '2024-01-20T10:30:00Z',
    uploader: 'STAFF001',
    keterangan: 'Dokumen requirement enkripsi database'
  },
  {
    id_dokumen: 'DOK002',
    nama_file: 'Testing_Enkripsi_Screenshot.png',
    tipe_file: 'image/png',
    ukuran_file: 1234567,
    path_file: '/documents/penanganan/DOK002.png',
    tgl_upload: '2024-02-05T15:00:00Z',
    uploader: 'STAFF001',
    keterangan: 'Screenshot hasil testing enkripsi'
  },
  {
    id_dokumen: 'DOK003',
    nama_file: 'Penetration_Test_Report_Feb2024.pdf',
    tipe_file: 'application/pdf',
    ukuran_file: 3456789,
    path_file: '/documents/penanganan/DOK003.pdf',
    tgl_upload: '2024-02-05T15:15:00Z',
    uploader: 'STAFF001',
    keterangan: 'Laporan penetration testing'
  },
  {
    id_dokumen: 'DOK004',
    nama_file: 'Daftar_Hadir_Training_Feb2024.xlsx',
    tipe_file: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ukuran_file: 456789,
    path_file: '/documents/penanganan/DOK004.xlsx',
    tgl_upload: '2024-02-15T14:00:00Z',
    uploader: 'STAFF002',
    keterangan: 'Daftar hadir training'
  },
  {
    id_dokumen: 'DOK005',
    nama_file: 'Hasil_PostTest_Training.pdf',
    tipe_file: 'application/pdf',
    ukuran_file: 987654,
    path_file: '/documents/penanganan/DOK005.pdf',
    tgl_upload: '2024-02-15T14:30:00Z',
    uploader: 'STAFF002',
    keterangan: 'Hasil post-test training'
  },
  {
    id_dokumen: 'DOK006',
    nama_file: 'Polis_Asuransi_IT_Draft.pdf',
    tipe_file: 'application/pdf',
    ukuran_file: 2345678,
    path_file: '/documents/penanganan/DOK006.pdf',
    tgl_upload: '2024-02-01T12:00:00Z',
    uploader: 'STAFF003',
    keterangan: 'Draft polis asuransi'
  },
  // SOP Documents
  {
    id_dokumen: 'DOK101',
    nama_file: 'SOP_Backup_Recovery_v2.1.pdf',
    tipe_file: 'application/pdf',
    ukuran_file: 1456789,
    path_file: '/documents/sop/DOK101.pdf',
    tgl_upload: '2023-06-01T08:00:00Z',
    uploader: 'ADMIN001',
    keterangan: 'SOP Backup dan Recovery Data versi 2.1'
  },
  {
    id_dokumen: 'DOK102',
    nama_file: 'SOP_Manajemen_Akses_v1.5.pdf',
    tipe_file: 'application/pdf',
    ukuran_file: 1234567,
    path_file: '/documents/sop/DOK102.pdf',
    tgl_upload: '2023-08-15T09:00:00Z',
    uploader: 'ADMIN001',
    keterangan: 'SOP Manajemen Akses Pengguna versi 1.5'
  },
  {
    id_dokumen: 'DOK103',
    nama_file: 'SOP_Incident_Response_v1.0.pdf',
    tipe_file: 'application/pdf',
    ukuran_file: 1567890,
    path_file: '/documents/sop/DOK103.pdf',
    tgl_upload: '2023-09-20T10:00:00Z',
    uploader: 'ADMIN001',
    keterangan: 'SOP Incident Response Keamanan versi 1.0'
  },
  {
    id_dokumen: 'DOK104',
    nama_file: 'SOP_Patch_Management_v1.2.pdf',
    tipe_file: 'application/pdf',
    ukuran_file: 1123456,
    path_file: '/documents/sop/DOK104.pdf',
    tgl_upload: '2023-10-10T11:00:00Z',
    uploader: 'ADMIN001',
    keterangan: 'SOP Patch Management versi 1.2'
  },
  {
    id_dokumen: 'DOK105',
    nama_file: 'SOP_Klasifikasi_Data_v2.0.pdf',
    tipe_file: 'application/pdf',
    ukuran_file: 1678901,
    path_file: '/documents/sop/DOK105.pdf',
    tgl_upload: '2023-11-01T12:00:00Z',
    uploader: 'ADMIN001',
    keterangan: 'SOP Klasifikasi dan Penanganan Data versi 2.0'
  }
];

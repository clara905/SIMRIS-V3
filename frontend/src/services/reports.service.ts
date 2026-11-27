/**
 * Reports & Analytics Service
 * 
 * Consolidates data from existing modules:
 * - Asset Management (asset.service.ts)
 * - Risk Assessment (asesmen.service.ts - MOCK_RISIKO, MOCK_PENILAIAN, MOCK_RISIKO_ASET)
 * - Risk Treatment (penanganan.service.ts - MOCK_RENCANA_PENANGANAN)
 * - Documentation & SOP (document.service.ts)
 * 
 * All data is derived from existing service mock data.
 * No duplicate datasets. No invented statistics.
 * ERD-compliant relationships maintained.
 */

import { assetService } from './asset.service';
import { documentService } from './document.service';
import { asesmenService, risikoService, penilaianService, risikoAsetService } from './asesmen.service';
import { rencanaPenangananService, progresService } from './penanganan.service';
import type { Asset, AssetCategory, AssetCondition, AssetStatus } from '@/types/risk.types';
import type { Dokumen, SOP } from '@/types/document.types';

// ─── Report Dashboard Stats ──────────────────────────────────────────────────

export interface ReportDashboardStats {
  // Assets
  totalAssets: number;
  assetsByCategory: { category: AssetCategory; count: number }[];
  assetsByCondition: { condition: AssetCondition; count: number }[];
  assetsByStatus: { status: AssetStatus; count: number }[];
  
  // Risks (from existing mock data - will connect to asesmen service)
  totalRisks: number;
  risksByLevel: { level: string; count: number; color: string }[];
  criticalRisks: number;
  
  // Treatments (from existing mock data - will connect to penanganan service)
  totalTreatments: number;
  treatmentsByStatus: { status: string; count: number; color: string }[];
  completedTreatments: number;
  inProgressTreatments: number;
  
  // Documents
  totalDocuments: number;
  documentsByType: { type: string; count: number }[];
  
  // SOPs
  totalSOPs: number;
  activeSOPs: number;
}

// ─── Asset Report Data ───────────────────────────────────────────────────────

export interface AssetReportSummary {
  totalAssets: number;
  byCategory: Record<string, number>;
  byCondition: Record<string, number>;
  byStatus: Record<string, number>;
  totalValue: number;
  avgCIA: { C: number; I: number; A: number };
}

export interface AssetReportFilters {
  search?: string;
  category?: string;
  condition?: string;
  status?: string;
  classification?: string;
}

// ─── Risk Assessment Report Data ─────────────────────────────────────────────

/**
 * Phase 8.1: Risk report data now uses actual RISIKO + PENILAIAN_RISIKO from asesmen.service.ts
 * No duplicate mock data. Real ERD-compliant relationships.
 */

export interface RiskReportSummary {
  totalRisks: number;
  byLevel: Record<string, number>;
  byCategory: Record<string, number>;
  avgLikelihood: number;
  avgImpact: number;
}

export interface RiskReportItem {
  id: number;
  kode: string;
  skenario: string;
  level: string; // 'Ekstrem', 'Tinggi', 'Sedang', 'Rendah', 'Sangat Rendah' from PENILAIAN_RISIKO
  levelColor: string;
  aset: string[]; // Asset codes from RISIKO_ASET
  kemungkinan: number | null;
  dampak: number | null;
  tingkat_risiko_nama: string | null;
}

export interface RiskReportFilters {
  level?: string;
  search?: string;
}

// ─── Treatment Report Data ───────────────────────────────────────────────────

/**
 * Phase 8.1: Treatment report data now uses actual RENCANA_PENANGANAN from penanganan.service.ts
 * No duplicate mock data. Real ERD-compliant relationships.
 */

export interface TreatmentReportSummary {
  totalTreatments: number;
  byStatus: Record<string, number>;
  byStrategy: Record<string, number>;
  avgProgress: number;
  overdueTreatments: number;
}

export interface TreatmentReportItem {
  id_rencana: string; // Primary key from RENCANA_PENANGANAN
  id_risiko: string;
  deskripsi: string;
  strategi: string;
  status: string;
  progres?: number;
  pic: string;
  tgl_target: string;
}

export interface TreatmentReportFilters {
  status?: string;
  search?: string;
}

// ─── Service Functions ───────────────────────────────────────────────────────

export const reportsService = {
  /**
   * Get comprehensive dashboard statistics
   * Phase 8.1: Uses real data from all existing services
   */
  async getDashboardStats(): Promise<ReportDashboardStats> {
    await new Promise(resolve => setTimeout(resolve, 400));

    // Get actual asset data (paginated - fetch all pages with safety checks)
    let allAssets: Asset[] = [];
    let currentPage = 1;
    let hasMorePages = true;
    const MAX_PAGES = 1000; // Safety limit to prevent infinite loops
    
    while (hasMorePages && currentPage <= MAX_PAGES) {
      const assetResult = await assetService.list({ page: currentPage });
      
      // Validate pagination response structure
      if (!assetResult || !assetResult.data || !assetResult.meta) {
        console.error('[reportsService.getDashboardStats] Invalid pagination response structure:', assetResult);
        throw new Error('Invalid pagination response from asset service');
      }
      
      // Validate last_page is a valid positive integer
      const lastPage = assetResult.meta.last_page;
      if (
        typeof lastPage !== 'number' || 
        !isFinite(lastPage) || 
        lastPage < 1 || 
        !Number.isInteger(lastPage)
      ) {
        console.error('[reportsService.getDashboardStats] Invalid last_page value:', lastPage);
        throw new Error('Invalid pagination metadata: last_page must be a positive integer');
      }
      
      allAssets = allAssets.concat(assetResult.data);
      hasMorePages = currentPage < lastPage;
      currentPage++;
    }
    
    // Check if pagination was truncated
    if (currentPage > MAX_PAGES && hasMorePages) {
      console.error('[reportsService.getDashboardStats] Pagination limit exceeded while more pages remain');
      throw new Error(`Pagination exceeded maximum limit of ${MAX_PAGES} pages. Dataset too large or pagination metadata invalid.`);
    }
    
    const assets = allAssets;

    // Calculate asset statistics from real data
    const assetsByCategory: { category: AssetCategory; count: number }[] = [];
    const categoryMap = new Map<AssetCategory, number>();
    
    assets.forEach(asset => {
      categoryMap.set(asset.kategori, (categoryMap.get(asset.kategori) || 0) + 1);
    });
    
    categoryMap.forEach((count, category) => {
      assetsByCategory.push({ category, count });
    });

    const assetsByCondition: { condition: AssetCondition; count: number }[] = [];
    const conditionMap = new Map<AssetCondition, number>();
    
    assets.forEach(asset => {
      conditionMap.set(asset.kondisi, (conditionMap.get(asset.kondisi) || 0) + 1);
    });
    
    conditionMap.forEach((count, condition) => {
      assetsByCondition.push({ condition, count });
    });

    const assetsByStatus: { status: AssetStatus; count: number }[] = [];
    const statusMap = new Map<AssetStatus, number>();
    
    assets.forEach(asset => {
      statusMap.set(asset.status, (statusMap.get(asset.status) || 0) + 1);
    });
    
    statusMap.forEach((count, status) => {
      assetsByStatus.push({ status, count });
    });

    // Risk statistics from actual risk assessment service
    const allRisiko = await risikoService.getAll();
    const allPenilaian = await penilaianService.getAll();
    
    // Build a map of risk ID to assessment level
    const riskLevelMap = new Map<number, { nama: string; warna: string }>();
    allPenilaian.forEach(p => {
      riskLevelMap.set(p.risiko_id, {
        nama: p.tingkat_risiko_nama,
        warna: p.tingkat_risiko_warna
      });
    });

    const levelCounts = {
      'Ekstrem': 0,
      'Tinggi': 0,
      'Sedang': 0,
      'Rendah': 0,
      'Sangat Rendah': 0,
    };

    allRisiko.forEach(r => {
      const level = riskLevelMap.get(r.id);
      if (level && levelCounts.hasOwnProperty(level.nama)) {
        levelCounts[level.nama]++;
      }
    });

    const risksByLevel = [
      { level: 'Ekstrem', count: levelCounts['Ekstrem'], color: '#762127' },
      { level: 'Tinggi', count: levelCounts['Tinggi'], color: '#A65316' },
      { level: 'Sedang', count: levelCounts['Sedang'], color: '#8A6B00' },
      { level: 'Rendah', count: levelCounts['Rendah'], color: '#16A34A' },
      { level: 'Sangat Rendah', count: levelCounts['Sangat Rendah'], color: '#065F46' },
    ].filter(item => item.count > 0);

    // Treatment statistics from actual treatment service
    const allTreatments = await rencanaPenangananService.getAll();
    
    const statusCounts = {
      'selesai': 0,
      'sedang_berjalan': 0,
      'belum_mulai': 0,
      'terlambat': 0,
    };

    const now = new Date();
    allTreatments.forEach(t => {
      const targetDate = new Date(t.tgl_target);
      if (t.status === 'selesai') {
        statusCounts['selesai']++;
      } else if (targetDate < now && t.status !== 'selesai') {
        statusCounts['terlambat']++;
      } else {
        statusCounts[t.status as keyof typeof statusCounts]++;
      }
    });

    const treatmentsByStatus = [
      { status: 'Selesai', count: statusCounts.selesai, color: '#16A34A' },
      { status: 'Berjalan', count: statusCounts.sedang_berjalan, color: '#2563EB' },
      { status: 'Belum Mulai', count: statusCounts.belum_mulai, color: '#9CA3AF' },
      { status: 'Terlambat', count: statusCounts.terlambat, color: '#DC2626' },
    ];

    // Document statistics
    const docStats = await documentService.getStats();
    const docsByType = [
      { type: 'Asesmen', count: docStats.dokumenAsesmen },
      { type: 'Penanganan', count: docStats.dokumenPenanganan },
      { type: 'SOP', count: docStats.dokumenSOP },
    ];

    // SOP statistics
    const sopResult = await documentService.getSOPs({ page: 1, per_page: 1000 });
    const activeSOPs = sopResult.data.filter(s => s.status === 'aktif').length;

    return {
      // Assets
      totalAssets: assets.length,
      assetsByCategory,
      assetsByCondition,
      assetsByStatus,
      
      // Risks
      totalRisks: allRisiko.length,
      risksByLevel,
      criticalRisks: levelCounts['Ekstrem'],
      
      // Treatments
      totalTreatments: allTreatments.length,
      treatmentsByStatus,
      completedTreatments: statusCounts.selesai,
      inProgressTreatments: statusCounts.sedang_berjalan,
      
      // Documents
      totalDocuments: docStats.totalDokumen,
      documentsByType: docsByType,
      
      // SOPs
      totalSOPs: sopResult.data.length,
      activeSOPs,
    };
  },

  /**
   * Get asset report summary
   */
  async getAssetReportSummary(): Promise<AssetReportSummary> {
    await new Promise(resolve => setTimeout(resolve, 300));

    // Get actual asset data (paginated - fetch all pages with safety checks)
    let allAssets: Asset[] = [];
    let currentPage = 1;
    let hasMorePages = true;
    const MAX_PAGES = 1000; // Safety limit to prevent infinite loops
    
    while (hasMorePages && currentPage <= MAX_PAGES) {
      const assetResult = await assetService.list({ page: currentPage });
      
      // Validate pagination response structure
      if (!assetResult || !assetResult.data || !assetResult.meta) {
        console.error('[reportsService.getAssetReportSummary] Invalid pagination response structure:', assetResult);
        throw new Error('Invalid pagination response from asset service');
      }
      
      // Validate last_page is a valid positive integer
      const lastPage = assetResult.meta.last_page;
      if (
        typeof lastPage !== 'number' || 
        !isFinite(lastPage) || 
        lastPage < 1 || 
        !Number.isInteger(lastPage)
      ) {
        console.error('[reportsService.getAssetReportSummary] Invalid last_page value:', lastPage);
        throw new Error('Invalid pagination metadata: last_page must be a positive integer');
      }
      
      allAssets = allAssets.concat(assetResult.data);
      hasMorePages = currentPage < lastPage;
      currentPage++;
    }
    
    // Check if pagination was truncated
    if (currentPage > MAX_PAGES && hasMorePages) {
      console.error('[reportsService.getAssetReportSummary] Pagination limit exceeded while more pages remain');
      throw new Error(`Pagination exceeded maximum limit of ${MAX_PAGES} pages. Dataset too large or pagination metadata invalid.`);
    }
    
    const assets = allAssets;

    const byCategory: Record<string, number> = {};
    const byCondition: Record<string, number> = {};
    const byStatus: Record<string, number> = {};
    let totalValue = 0;
    let totalC = 0, totalI = 0, totalA = 0;

    assets.forEach(asset => {
      byCategory[asset.kategori] = (byCategory[asset.kategori] || 0) + 1;
      byCondition[asset.kondisi] = (byCondition[asset.kondisi] || 0) + 1;
      byStatus[asset.status] = (byStatus[asset.status] || 0) + 1;
      totalValue += asset.nilai_aset;
      totalC += asset.kerahasiaan;
      totalI += asset.integritas;
      totalA += asset.ketersediaan;
    });

    return {
      totalAssets: assets.length,
      byCategory,
      byCondition,
      byStatus,
      totalValue,
      avgCIA: {
        C: assets.length > 0 ? totalC / assets.length : 0,
        I: assets.length > 0 ? totalI / assets.length : 0,
        A: assets.length > 0 ? totalA / assets.length : 0,
      },
    };
  },

  /**
   * Get filtered assets for reports
   */
  async getAssetReportData(filters: AssetReportFilters) {
    await new Promise(resolve => setTimeout(resolve, 300));

    // Get filtered asset data (paginated - fetch all pages with safety checks)
    let allAssets: Asset[] = [];
    let currentPage = 1;
    let hasMorePages = true;
    const MAX_PAGES = 1000; // Safety limit to prevent infinite loops
    
    while (hasMorePages && currentPage <= MAX_PAGES) {
      const result = await assetService.list({
        page: currentPage,
        q: filters.search,
        kategori: filters.category,
        kondisi: filters.condition,
        status: filters.status,
      });
      
      // Validate pagination response structure
      if (!result || !result.data || !result.meta) {
        console.error('[reportsService.getAssetReportData] Invalid pagination response structure:', result);
        throw new Error('Invalid pagination response from asset service');
      }
      
      // Validate last_page is a valid positive integer
      const lastPage = result.meta.last_page;
      if (
        typeof lastPage !== 'number' || 
        !isFinite(lastPage) || 
        lastPage < 1 || 
        !Number.isInteger(lastPage)
      ) {
        console.error('[reportsService.getAssetReportData] Invalid last_page value:', lastPage);
        throw new Error('Invalid pagination metadata: last_page must be a positive integer');
      }
      
      allAssets = allAssets.concat(result.data);
      hasMorePages = currentPage < lastPage;
      currentPage++;
    }
    
    // Check if pagination was truncated
    if (currentPage > MAX_PAGES && hasMorePages) {
      console.error('[reportsService.getAssetReportData] Pagination limit exceeded while more pages remain');
      throw new Error(`Pagination exceeded maximum limit of ${MAX_PAGES} pages. Dataset too large or pagination metadata invalid.`);
    }

    return allAssets;
  },

  /**
   * Get risk report summary
   * Phase 8.1: Uses actual RISIKO + PENILAIAN_RISIKO data
   */
  async getRiskReportSummary(): Promise<RiskReportSummary> {
    await new Promise(resolve => setTimeout(resolve, 300));

    const allRisiko = await risikoService.getAll();
    const allPenilaian = await penilaianService.getAll();

    // Build a map of risk ID to assessment
    const riskAssessmentMap = new Map<number, typeof allPenilaian[0]>();
    allPenilaian.forEach(p => {
      riskAssessmentMap.set(p.risiko_id, p);
    });

    const byLevel: Record<string, number> = {};
    const byCategory: Record<string, number> = {};

    let totalLikelihood = 0;
    let totalImpact = 0;
    let countWithAssessment = 0;

    allRisiko.forEach(r => {
      const assessment = riskAssessmentMap.get(r.id);
      if (assessment) {
        const level = assessment.tingkat_risiko_nama;
        byLevel[level] = (byLevel[level] || 0) + 1;

        if (assessment.kemungkinan !== null && assessment.dampak !== null) {
          totalLikelihood += assessment.kemungkinan;
          totalImpact += assessment.dampak;
          countWithAssessment++;
        }
      }

      // Category by assessment (could group by threat type, unit, etc.)
      const asesmenName = r.asesmen_nama;
      byCategory[asesmenName] = (byCategory[asesmenName] || 0) + 1;
    });

    return {
      totalRisks: allRisiko.length,
      byLevel,
      byCategory,
      avgLikelihood: countWithAssessment > 0 ? totalLikelihood / countWithAssessment : 0,
      avgImpact: countWithAssessment > 0 ? totalImpact / countWithAssessment : 0,
    };
  },

  /**
   * Get risk report data with filters
   * Phase 8.1: Uses actual RISIKO + PENILAIAN_RISIKO + RISIKO_ASET data
   */
  async getRiskReportData(filters?: RiskReportFilters): Promise<RiskReportItem[]> {
    await new Promise(resolve => setTimeout(resolve, 300));

    const allRisiko = await risikoService.getAll();
    const allPenilaian = await penilaianService.getAll();
    const allRisikoAset = await risikoAsetService.getAll();

    // Build maps
    const riskAssessmentMap = new Map<number, typeof allPenilaian[0]>();
    allPenilaian.forEach(p => {
      riskAssessmentMap.set(p.risiko_id, p);
    });

    const riskAssetsMap = new Map<number, string[]>();
    allRisikoAset.forEach(ra => {
      if (!riskAssetsMap.has(ra.risiko_id)) {
        riskAssetsMap.set(ra.risiko_id, []);
      }
      riskAssetsMap.get(ra.risiko_id)!.push(ra.kode_aset_snapshot);
    });

    // Convert to report items
    let items: RiskReportItem[] = allRisiko.map(r => {
      const assessment = riskAssessmentMap.get(r.id);
      const assets = riskAssetsMap.get(r.id) || [];

      return {
        id: r.id,
        kode: r.kode,
        skenario: r.skenario,
        level: assessment?.tingkat_risiko_nama || 'Belum Dinilai',
        levelColor: assessment?.tingkat_risiko_warna || '#6B7280',
        aset: assets,
        kemungkinan: assessment?.kemungkinan || null,
        dampak: assessment?.dampak || null,
        tingkat_risiko_nama: assessment?.tingkat_risiko_nama || null,
      };
    });

    // Apply filters
    if (filters?.level) {
      items = items.filter(r => r.level === filters.level);
    }

    if (filters?.search) {
      const search = filters.search.toLowerCase();
      items = items.filter(r =>
        r.kode.toLowerCase().includes(search) ||
        r.skenario.toLowerCase().includes(search)
      );
    }

    return items;
  },

  /**
   * Get treatment report summary
   * Phase 8.1: Uses actual RENCANA_PENANGANAN data
   */
  async getTreatmentReportSummary(): Promise<TreatmentReportSummary> {
    await new Promise(resolve => setTimeout(resolve, 300));

    const allTreatments = await rencanaPenangananService.getAll();

    const byStatus: Record<string, number> = {};
    const byStrategy: Record<string, number> = {};
    let totalProgress = 0;
    let overdue = 0;

    const now = new Date();

    // Calculate progress for each treatment
    for (const treatment of allTreatments) {
      // Determine actual status (check for overdue)
      const targetDate = new Date(treatment.tgl_target);
      let actualStatus = treatment.status;
      
      if (targetDate < now && treatment.status !== 'selesai') {
        actualStatus = 'terlambat';
        overdue++;
      }

      byStatus[actualStatus] = (byStatus[actualStatus] || 0) + 1;
      byStrategy[treatment.strategi] = (byStrategy[treatment.strategi] || 0) + 1;

      // Calculate progress: get latest progress entry
      const progressEntries = await progresService.getByRencana(treatment.id_rencana);
      if (progressEntries.length > 0) {
        // Sort by date and get latest
        const latest = progressEntries.sort((a, b) => 
          new Date(b.tgl_progres).getTime() - new Date(a.tgl_progres).getTime()
        )[0];
        totalProgress += latest.persentase_progres;
      } else if (treatment.status === 'selesai') {
        totalProgress += 100;
      }
    }

    return {
      totalTreatments: allTreatments.length,
      byStatus,
      byStrategy,
      avgProgress: allTreatments.length > 0 ? totalProgress / allTreatments.length : 0,
      overdueTreatments: overdue,
    };
  },

  /**
   * Get treatment report data with filters
   * Phase 8.1: Uses actual RENCANA_PENANGANAN data
   */
  async getTreatmentReportData(filters?: TreatmentReportFilters): Promise<TreatmentReportItem[]> {
    await new Promise(resolve => setTimeout(resolve, 300));

    const allTreatments = await rencanaPenangananService.getAll();

    // Convert to report items with progress
    const items: TreatmentReportItem[] = await Promise.all(
      allTreatments.map(async (t) => {
        // Get latest progress
        const progressEntries = await progresService.getByRencana(t.id_rencana);
        let progres = 0;
        
        if (progressEntries.length > 0) {
          const latest = progressEntries.sort((a, b) => 
            new Date(b.tgl_progres).getTime() - new Date(a.tgl_progres).getTime()
          )[0];
          progres = latest.persentase_progres;
        } else if (t.status === 'selesai') {
          progres = 100;
        }

        return {
          id_rencana: t.id_rencana,
          id_risiko: t.id_risiko,
          deskripsi: t.deskripsi,
          strategi: t.strategi,
          status: t.status,
          progres,
          pic: t.pic,
          tgl_target: t.tgl_target,
        };
      })
    );

    // Apply filters
    let filtered = items;

    if (filters?.status) {
      filtered = filtered.filter(t => t.status === filters.status);
    }

    if (filters?.search) {
      const search = filters.search.toLowerCase();
      filtered = filtered.filter(t =>
        t.id_rencana.toLowerCase().includes(search) ||
        t.id_risiko.toLowerCase().includes(search) ||
        t.deskripsi.toLowerCase().includes(search)
      );
    }

    return filtered;
  },

  /**
   * Get document report data
   */
  async getDocumentReportData() {
    await new Promise(resolve => setTimeout(resolve, 300));

    const docStats = await documentService.getStats();
    const sopResult = await documentService.getSOPs({ page: 1, per_page: 1000 });

    return {
      totalDocuments: docStats.totalDokumen,
      documentsByType: [
        { type: 'Asesmen', count: docStats.dokumenAsesmen },
        { type: 'Penanganan', count: docStats.dokumenPenanganan },
        { type: 'SOP', count: docStats.dokumenSOP },
      ],
      recentDocuments: docStats.recentDocuments,
      totalSOPs: sopResult.data.length,
      activeSOPs: sopResult.data.filter(s => s.status === 'aktif').length,
      sops: sopResult.data,
    };
  },
};

<!-- TOP PROMO ANNOUNCEMENT BAR (SESUAI ACUAN CODE BERANDA) -->
<style>
.topbar-promo {
  background: linear-gradient(90deg, #88133d 0%, #be124f 35%, #e11d64 70%, #f43f7e 100%) !important;
  color: #ffffff !important;
  font-size: 13px !important;
  padding: 8px 0 !important;
  position: relative !important;
  z-index: 50 !important;
  box-shadow: 0 2px 10px rgba(136, 19, 61, 0.25) !important;
  font-family: 'Poppins', sans-serif !important;
}
.topbar-promo .container {
  max-width: 1220px !important;
  padding-left: 20px !important;
  padding-right: 20px !important;
}
.topbar-content {
  display: flex !important;
  align-items: center !important;
  justify-content: space-between !important;
  flex-wrap: wrap !important;
  gap: 8px !important;
}
.topbar-promo-text {
  display: flex !important;
  align-items: center !important;
  gap: 8px !important;
  font-weight: 500 !important;
  font-size: 12.5px !important;
  color: #ffffff !important;
}
.topbar-badge {
  display: inline-flex !important;
  align-items: center !important;
  padding: 2px 10px !important;
  border-radius: 9999px !important;
  font-size: 10.5px !important;
  font-weight: 800 !important;
  background: #ffffff !important;
  color: #be124f !important;
  text-transform: uppercase !important;
  letter-spacing: 0.5px !important;
}
.promo-code {
  text-decoration: underline !important;
  color: #ffe4ed !important;
  cursor: pointer !important;
}
.topbar-info {
  display: flex !important;
  align-items: center !important;
  gap: 12px !important;
  color: #ffe4ed !important;
  font-size: 12px !important;
}
.topbar-separator {
  opacity: 0.6 !important;
}
.topbar-wa-link {
  color: #ffffff !important;
  text-decoration: none !important;
  font-weight: 600 !important;
  display: inline-flex !important;
  align-items: center !important;
  gap: 4px !important;
  background: rgba(255, 255, 255, 0.15) !important;
  padding: 2px 10px !important;
  border-radius: 20px !important;
  width: auto !important;
  height: auto !important;
}
.topbar-wa-link:hover {
  background: rgba(255, 255, 255, 0.25) !important;
  color: #ffffff !important;
}
@media (max-width: 767.98px) {
  .topbar-promo {
    padding: 3px 0 !important;
    min-height: auto !important;
    font-size: 8.8px !important;
    line-height: 1.15 !important;
    overflow: hidden !important;
  }
  .topbar-content {
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    flex-wrap: nowrap !important;
    gap: 2px !important;
    width: 100% !important;
    text-align: center !important;
  }
  .topbar-promo-text {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px !important;
    font-size: 8.8px !important;
    line-height: 1.15 !important;
    white-space: nowrap !important;
    width: 100% !important;
    overflow: hidden !important;
  }
  .topbar-promo-text > span:last-child {
    white-space: nowrap !important;
    overflow: hidden !important;
    text-overflow: ellipsis !important;
    font-size: 8.8px !important;
    display: inline-block !important;
  }
  .topbar-badge {
    font-size: 7.5px !important;
    padding: 1px 5px !important;
    white-space: nowrap !important;
    letter-spacing: 0.3px !important;
    line-height: 1.1 !important;
    flex-shrink: 0 !important;
  }
  .promo-code {
    font-size: 8.8px !important;
    white-space: nowrap !important;
  }
  .topbar-info {
    display: flex !important;
    flex-direction: row !important;
    flex-wrap: nowrap !important;
    align-items: center !important;
    justify-content: center !important;
    gap: 4px !important;
    font-size: 8px !important;
    line-height: 1.15 !important;
    color: #ffe4ed !important;
    white-space: nowrap !important;
    width: 100% !important;
    overflow: hidden !important;
  }
  .topbar-info span {
    font-size: 8px !important;
    white-space: nowrap !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 2.5px !important;
    flex-shrink: 0 !important;
  }
  .topbar-wa-link {
    font-size: 8px !important;
    padding: 1px 6px !important;
    white-space: nowrap !important;
    gap: 2.5px !important;
    border-radius: 999px !important;
    flex-shrink: 0 !important;
    line-height: 1.15 !important;
  }
}
</style>
<div class="topbar-promo">
    <div class="container">
        <div class="topbar-content">
            <div class="topbar-promo-text">
                <span class="topbar-badge">Promo Spesial</span>
                <span>Diskon <strong>20%</strong> Cetak Kemasan, Undangan &amp; Stiker! Kode: <strong class="promo-code">ROSAHEMAT10</strong></span>
            </div>
            <div class="topbar-info">
                <span><i class="bi bi-clock"></i> 08.00 - 21.00 WIB</span>
                <span class="topbar-separator">•</span>
                <span><i class="bi bi-truck"></i> Pengiriman Seluruh Indonesia</span>
                <span class="topbar-separator">•</span>
                <a href="https://wa.me/<?php echo defined('WHATSAPP_NUMBER') ? WHATSAPP_NUMBER : '6282331035371'; ?>" target="_blank" rel="noopener" class="topbar-wa-link">
                    <i class="bi bi-whatsapp"></i> 0823-3103-5371
                </a>
            </div>
        </div>
    </div>
</div>
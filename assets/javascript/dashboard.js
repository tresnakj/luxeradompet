// ============================================

// PRICE CONFIGURATION - TOKOCRYPTO & COINGECKO

// ============================================

const PRICE_CONFIG = {

  // TokoCrypto API Configuration

  tokocrypto: {

    baseUrl: "https://www.tokocrypto.site/api/v3",

    backupUrl: "https://cloudme-toko.2meta.app/api/v1",

    endpoints: {

      ticker24h: "/ticker/24hr",

      tickerPrice: "/ticker/price",

    },

  },

  usdtIdr: 16950,

  xeraPrice: 29.87,

  lastUpdate: null,

  updateInterval: 30000, // 30 detik

  lastUsdtIdrRate: null,

};



// ============================================

// GLOBAL VARIABLES untuk Airdrop Wallet Terpilih (TAMBAHAN)

// ============================================

window.selectedWalletAirdrop = 0;

window.selectedWalletName = "";

window.selectedWalletAddress = "";

window.currentXeraPrice = PRICE_CONFIG.xeraPrice;

window.currentUsdtRate = PRICE_CONFIG.usdtIdr;



// Format helpers

function formatRupiah(angka) {

  if (typeof angka !== "number") return "Rp 0";

  return "Rp " + Math.floor(angka).toLocaleString("id-ID");

}



function formatUSD(amount) {

  if (typeof amount !== "number") return "$0.00 USDT";

  return (

    "$" +

    parseFloat(amount).toLocaleString("en-US", {

      minimumFractionDigits: 2,

      maximumFractionDigits: 2,

    }) +

    " USDT"

  );

}



function formatKoin(angka) {

  if (typeof angka !== "number") return "0";

  return angka.toLocaleString("id-ID", {

    minimumFractionDigits: 0,

    maximumFractionDigits: 8,

  });

}



function updatePriceElement(elementId, newValue, isChange = false) {

  const element = document.getElementById(elementId);

  if (!element) return;

  const oldValue = element.textContent;

  if (oldValue !== newValue) {

    element.textContent = newValue;

    element.classList.add("price-update");

    if (isChange) {

      const numChange = parseFloat(newValue);

      element.classList.remove("price-up", "price-down");

      if (numChange > 0) {

        element.classList.add("price-up");

        element.innerHTML = `📈 +${newValue}% (24h)`;

      } else if (numChange < 0) {

        element.classList.add("price-down");

        element.innerHTML = `📉 ${newValue}% (24h)`;

      } else {

        element.innerHTML = `➡️ 0.00% (24h)`;

      }

    }

    setTimeout(() => element.classList.remove("price-update"), 500);

  }

}



// ============================================

// UPDATE AIRDROP WALLET TERPILIH (TAMBAHAN BARU)

// ============================================

function updateSelectedAirdropConversions() {

  const airdrop = window.selectedWalletAirdrop || 0;

  const xeraPrice = window.currentXeraPrice;

  const usdtRate = window.currentUsdtRate;



  const airdropUSD = airdrop * xeraPrice;

  const airdropIDR = airdrop * xeraPrice * usdtRate;



  const rpEl = document.getElementById("selected-airdrop-rp");

  const usdEl = document.getElementById("selected-airdrop-usd");

  const xeraEl = document.getElementById("selected-airdrop-xera");



  if (rpEl) rpEl.textContent = `≈ ${formatRupiah(airdropIDR)}`;

  if (usdEl) usdEl.textContent = `≈ ${formatUSD(airdropUSD)}`;

  if (xeraEl && airdrop !== undefined) {

    xeraEl.innerHTML = `${formatKoin(airdrop)} XERA`;

  }

}



// ============================================

// FETCH USDT/IDR DARI TOKOCRYPTO (ASLI - TIDAK DIUBAH)

// ============================================

async function fetchUSDTIDR() {

  console.log("🔄 Fetching USDT/IDR from TokoCrypto...");



  // Coba 1: TokoCrypto MBX Engine

  try {

    const response = await fetch(

      `${PRICE_CONFIG.tokocrypto.baseUrl}/ticker/price?symbol=USDTIDR`,

    );

    if (response.ok) {

      const data = await response.json();

      if (data && data.price) {

        const rate = parseFloat(data.price);

        console.log("✅ USDT/IDR dari TokoCrypto MBX:", rate);

        PRICE_CONFIG.lastUsdtIdrRate = rate;

        return rate;

      }

    }

  } catch (e) {

    console.log("⚠️ TokoCrypto MBX gagal");

  }



  // Coba 2: TokoCrypto NextMe Engine (dengan underscore)

  try {

    const response = await fetch(

      `${PRICE_CONFIG.tokocrypto.backupUrl}/ticker/price?symbol=USDT_IDR`,

    );

    if (response.ok) {

      const data = await response.json();

      if (data && data.price) {

        const rate = parseFloat(data.price);

        console.log("✅ USDT/IDR dari TokoCrypto NextMe:", rate);

        PRICE_CONFIG.lastUsdtIdrRate = rate;

        return rate;

      }

    }

  } catch (e) {

    console.log("⚠️ TokoCrypto NextMe gagal");

  }



  // Fallback: CoinGecko

  try {

    const response = await fetch(

      "https://api.coingecko.com/api/v3/simple/price?ids=tether&vs_currencies=idr",

    );

    const data = await response.json();

    if (data && data.tether && data.tether.idr) {

      console.log("✅ USDT/IDR dari CoinGecko:", data.tether.idr);

      PRICE_CONFIG.lastUsdtIdrRate = data.tether.idr;

      return data.tether.idr;

    }

  } catch (e) {

    console.log("❌ CoinGecko juga gagal");

  }



  return PRICE_CONFIG.usdtIdr;

}



// ============================================

// FETCH HARGA XERA (ASLI - KEMBALI KE SEMULA)

// ============================================

async function fetchXERAPrice() {

  try {

    const response = await fetch(

      "https://api.coingecko.com/api/v3/simple/price?ids=luxera&vs_currencies=usd,idr&include_24hr_change=true",

    );

    const data = await response.json();

    if (data && data.luxera && data.luxera.usd) {

      const xeraUSD = data.luxera.usd;

      // SELALU hitung IDR menggunakan rate TokoCrypto, ignore harga IDR dari CoinGecko

      const xeraIDR =

        xeraUSD * (PRICE_CONFIG.lastUsdtIdrRate || PRICE_CONFIG.usdtIdr);



      return {

        price: xeraUSD, // $29 (dari CoinGecko)

        priceIdr: xeraIDR, // Rp 491,150 (dihitung: 29 × 16,935 TokoCrypto rate)

        change: data.luxera.usd_24h_change || 0,

        source: "CoinGecko (Rate IDR via TokoCrypto)", // Indikasi mixed source

      };

    }

  } catch (e) {

    console.log("⚠️ CoinGecko gagal, mencoba DexScreener...");

  }



  // Fallback DexScreener

  try {

    const xeraContract = "0xcA7dF3c62AEe95E44D2B6eE51D1fD95a0b2d6688";

    const response = await fetch(

      `https://api.dexscreener.com/latest/dex/tokens/${xeraContract}`,

    );

    const data = await response.json();

    if (data && data.pairs && data.pairs.length > 0) {

      const pair = data.pairs[0];

      const price = parseFloat(pair.priceUsd);

      const usdtIdrRate = PRICE_CONFIG.lastUsdtIdrRate || PRICE_CONFIG.usdtIdr;

      return {

        price: price,

        priceIdr: price * usdtIdrRate,

        change: pair.priceChange ? parseFloat(pair.priceChange.h24) : 0,

        source: "DexScreener",

      };

    }

  } catch (e) {

    console.log("⚠️ DexScreener gagal");

  }



  const fallbackPrice = PRICE_CONFIG.xeraPrice;

  return {

    price: fallbackPrice,

    priceIdr:

      fallbackPrice * (PRICE_CONFIG.lastUsdtIdrRate || PRICE_CONFIG.usdtIdr),

    change: 0,

    source: "Fallback",

  };

}



// ============================================

// MAIN UPDATE FUNCTION (ASLI + TAMBAHAN AIRDROP)

// ============================================

async function updatePrices() {

  // Gunakan variabel global yang didefinisikan di inline PHP

  const totalInvestasi =

    window.currentTotalInvestasi !== undefined

      ? window.currentTotalInvestasi

      : 0;

  const totalAirdrop =

    window.totalAirdrop !== undefined ? window.totalAirdrop : 0;

  const totalStackingXera =

    window.currentTotalKoin !== undefined ? window.currentTotalKoin : 0;



  try {

    const usdtRate = await fetchUSDTIDR();

    const xeraData = await fetchXERAPrice();



    // Simpan ke global untuk digunakan update airdrop wallet terpilih

    window.currentXeraPrice = xeraData.price;

    window.currentUsdtRate = usdtRate;



    // Update UI elements

    const rateEl = document.getElementById("usdt-rate");

    if (rateEl) {

      rateEl.innerHTML = `1 USDT = ${formatRupiah(usdtRate)} <span style="color:#27ae60;font-size:10px">via TokoCrypto</span>`;

    }



    // Investasi

    const investasiUSDT = totalInvestasi / usdtRate;

    updatePriceElement("usdt-value", formatUSD(investasiUSDT));



    // Air Drop

    const airdropUSD = totalAirdrop * xeraData.price;

    const airdropIDR = totalAirdrop * xeraData.priceIdr;

    const airdropUsdEl = document.getElementById("airdrop-usd-value");

    const airdropRpEl = document.getElementById("airdrop-rp-value");

    if (airdropUsdEl) airdropUsdEl.textContent = `≈ ${formatUSD(airdropUSD)}`;

    if (airdropRpEl) airdropRpEl.textContent = `≈ ${formatRupiah(airdropIDR)}`;



    // XERA Price

    const xeraPriceEl = document.getElementById("xera-price");

    if (xeraPriceEl)

      xeraPriceEl.innerHTML = `$${xeraData.price.toFixed(2)} <span style="font-size:11px;color:#7f8c8d">via ${xeraData.source}</span>`;



    const xeraChangeEl = document.getElementById("xera-change");

    if (xeraChangeEl) {

      xeraChangeEl.classList.remove("price-up", "price-down");

      if (xeraData.change > 0) {

        xeraChangeEl.classList.add("price-up");

        xeraChangeEl.innerHTML = `📈 +${xeraData.change.toFixed(2)}% <small style="font-size:10px">(24h)</small>`;

      } else if (xeraData.change < 0) {

        xeraChangeEl.classList.add("price-down");

        xeraChangeEl.innerHTML = `📉 ${xeraData.change.toFixed(2)}% <small style="font-size:10px">(24h)</small>`;

      } else {

        xeraChangeEl.innerHTML = `➡️ 0.00% <small style="font-size:10px">(24h)</small>`;

      }

    }



    // Konversi 1 XERA

    const xeraUsdConvertEl = document.getElementById("xera-usd-convert");

    const xeraIdrConvertEl = document.getElementById("xera-idr-convert");

    if (xeraUsdConvertEl)

      xeraUsdConvertEl.textContent = `$${xeraData.price.toFixed(4)} USDT`;

    if (xeraIdrConvertEl)

      xeraIdrConvertEl.textContent = formatRupiah(xeraData.priceIdr);



    // Stacking

    const stackingUSD = totalStackingXera * xeraData.price;

    const stackingIDR = totalStackingXera * xeraData.priceIdr;

    const stackingUsdEl = document.getElementById("stacking-usd-value");

    const stackingRpEl = document.getElementById("stacking-rp-value");

    if (stackingUsdEl) stackingUsdEl.textContent = formatUSD(stackingUSD);

    if (stackingRpEl) stackingRpEl.textContent = formatRupiah(stackingIDR);



    // Profit

    const selisihProfit = stackingIDR - totalInvestasi;

    const profitPercent =

      totalInvestasi > 0 ? (selisihProfit / totalInvestasi) * 100 : 0;

    const profitEl = document.getElementById("profit-selisih");

    const profitPercentEl = document.getElementById("profit-percent");



    if (profitEl && profitPercentEl) {

      const selisihFormatted =

        selisihProfit >= 0

          ? `+${formatRupiah(selisihProfit)}`

          : `-${formatRupiah(Math.abs(selisihProfit))}`;



      profitEl.textContent = selisihFormatted;

      profitPercentEl.textContent = `${profitPercent >= 0 ? "+" : ""}${profitPercent.toFixed(2)}%`;



      if (selisihProfit >= 0) {

        profitEl.style.color = "#27ae60";

        profitPercentEl.style.color = "#27ae60";

        profitEl.innerHTML = `📈 ${selisihFormatted}`;

      } else {

        profitEl.style.color = "#e74c3c";

        profitPercentEl.style.color = "#e74c3c";

        profitEl.innerHTML = `📉 ${selisihFormatted}`;

      }

    }



    // Update airdrop wallet terpilih (konversi) - TAMBAHAN

    updateSelectedAirdropConversions();



    PRICE_CONFIG.lastUpdate = new Date();

    console.log("✅ Prices updated via TokoCrypto/Coingecko");

  } catch (error) {

    console.error("❌ Update error:", error);

    const rateEl = document.getElementById("usdt-rate");

    if (rateEl) {

      rateEl.textContent = "1 USDT = ~Rp 16,950 (Offline)";

      rateEl.classList.add("price-error");

    }

  }

}



// ============================================

// COUNTDOWN TIMER (ASLI - KEMBALI KE SEMULA)

// ============================================

(function () {

  const UPDATE_INTERVAL = Math.floor(PRICE_CONFIG.updateInterval / 1000);

  let timeLeft = UPDATE_INTERVAL;

  let isUpdating = false;



  const xeraCard = document.querySelector(".stat-card.xera-card");

  const barEl = document.getElementById("xeraCountdownBar");

  const timeEl = document.getElementById("xeraCountdownTime");

  const statusEl = document.getElementById("countdownStatus");

  const dotEl = document.getElementById("countdownDot");

  const ringEl = document.getElementById("updateRing");



  if (!barEl || !timeEl) return;



  function updateBar() {

    barEl.style.width = (timeLeft / UPDATE_INTERVAL) * 100 + "%";

  }



  function flashCard() {

    if (xeraCard) {

      xeraCard.classList.add("flashing");

      setTimeout(() => xeraCard.classList.remove("flashing"), 1000);

    }

    if (ringEl) {

      ringEl.classList.add("active");

      setTimeout(() => ringEl.classList.remove("active"), 1000);

    }

  }



  function setUrgentMode(isUrgent) {

    if (isUrgent) {

      barEl.classList.add("urgent");

      timeEl.classList.add("urgent");

    } else {

      barEl.classList.remove("urgent");

      timeEl.classList.remove("urgent");

    }

  }



  function setUpdatingMode(updating) {

    if (updating) {

      isUpdating = true;

      barEl.classList.add("updating");

      timeEl.classList.add("updating");

      if (dotEl) dotEl.classList.add("updating");

      if (statusEl) statusEl.textContent = "Updating from TokoCrypto...";

      timeEl.textContent = "...";

      barEl.style.width = "0%";

    } else {

      isUpdating = false;

      barEl.classList.remove("updating");

      timeEl.classList.remove("updating");

      if (dotEl) dotEl.classList.remove("updating");

      if (statusEl) statusEl.textContent = "Next update in";

      setUrgentMode(false);

    }

  }



  function tick() {

    if (!isUpdating) {

      timeLeft--;

      if (timeLeft <= 0) {

        setUpdatingMode(true);

        timeLeft = UPDATE_INTERVAL;

        flashCard();

        updatePrices()

          .then(() => {

            setTimeout(() => {

              setUpdatingMode(false);

              updateBar();

            }, 1500);

          })

          .catch(() => {

            setTimeout(() => {

              setUpdatingMode(false);

              updateBar();

            }, 1500);

          });

      } else {

        timeEl.textContent = timeLeft + "s";

        updateBar();

        const urgentThreshold =

          UPDATE_INTERVAL <= 15 ? 3 : Math.floor(UPDATE_INTERVAL * 0.2);

        if (timeLeft <= urgentThreshold && timeLeft > 0) {

          setUrgentMode(true);

        } else {

          setUrgentMode(false);

        }

      }

    }

  }



  // Sync dengan updatePrices external (untuk Filter AJAX)

  const originalUpdatePrices = window.updatePrices;

  window.updatePrices = async function () {

    setUpdatingMode(true);

    flashCard();

    try {

      await originalUpdatePrices.apply(this, arguments);

      setTimeout(() => {

        setUpdatingMode(false);

        timeLeft = UPDATE_INTERVAL;

        updateBar();

      }, 500);

    } catch (e) {

      setTimeout(() => {

        setUpdatingMode(false);

        timeLeft = UPDATE_INTERVAL;

        updateBar();

      }, 1000);

    }

  };



  document.addEventListener("visibilitychange", () => {

    if (document.visibilityState === "visible") {

      timeLeft = UPDATE_INTERVAL;

      setUpdatingMode(false);

      updateBar();

    }

  });



  timeEl.textContent = UPDATE_INTERVAL + "s";

  setInterval(tick, 1000);

  updateBar();

  console.log("⏱️ Countdown started:", UPDATE_INTERVAL + "s");

})();



// ============================================

// FILTER AJAX SCRIPT (TAMBAHAN AIRDROP)

// ============================================

(function () {

  // State

  let currentGroup = window.defaultGroup || "";

  let currentJenis = "Semua";

  let isLoading = false;



  // Elements

  const filterGrup = document.getElementById("filter-grup");

  const filterJenis = document.getElementById("filter-jenis");

  const filterStatus = document.getElementById("filter-status");

  const cardInvestasi = document.getElementById("card-investasi");

  const cardStacking = document.getElementById("card-stacking");



  // Info elements

  const infoGrupInv = document.getElementById("info-grup-investasi");

  const infoJenisInv = document.getElementById("info-jenis-investasi");

  const infoGrupStack = document.getElementById("info-grup-stacking");

  const infoJenisStack = document.getElementById("info-jenis-stacking");



  // Format functions (reuse from global or define locally)

  function formatRupiah(angka) {

    return "Rp " + Math.floor(angka).toLocaleString("id-ID");

  }



  function formatKoin(angka) {

    return parseFloat(angka).toLocaleString("id-ID", {

      minimumFractionDigits: 0,

      maximumFractionDigits: 8,

    });

  }



  function formatAddress(address) {

    if (!address || address.length <= 12) return address;

    return (

      address.substring(0, 6) + "..." + address.substring(address.length - 6)

    );

  }



  // Update UI with animation

  function updateStat(elementId, value, isRupiah = false) {

    const el = document.getElementById(elementId);

    if (!el) return;

    el.style.opacity = "0";

    el.style.transform = "translateY(-10px)";

    el.style.transition = "all 0.3s ease";



    setTimeout(() => {

      if (isRupiah) {

        el.textContent = formatRupiah(value);

      } else {

        el.textContent = formatKoin(value);

      }

      el.style.opacity = "1";

      el.style.transform = "translateY(0)";

    }, 150);

  }



  // Show loading state

  function setLoading(loading) {

    isLoading = loading;

    if (loading) {

      filterGrup.classList.add("filter-loading");

      filterJenis.classList.add("filter-loading");

      filterStatus.innerHTML =

        '<span class="price-loading" style="width: 16px; height: 16px; border-width: 2px; display: inline-block; vertical-align: middle; margin-right: 8px;"></span> Menghitung...';

      cardInvestasi.classList.add("updating");

      cardStacking.classList.add("updating");

    } else {

      filterGrup.classList.remove("filter-loading");

      filterJenis.classList.remove("filter-loading");

      cardInvestasi.classList.remove("updating");

      cardStacking.classList.remove("updating");

    }

  }



  // Fetch data from server

  async function fetchGroupStats() {

    if (isLoading) return;



    setLoading(true);



    try {

      const selectedOption = filterGrup.options[filterGrup.selectedIndex];

      const alamatGrup = selectedOption.getAttribute("data-alamat") || "";

      const namaGrup = selectedOption.getAttribute("data-nama") || "";



      const response = await fetch(

        `ajax/get_group_stats.php?group_code=${encodeURIComponent(currentGroup)}&jenis_filter=${encodeURIComponent(currentJenis)}&wallet_address=${encodeURIComponent(alamatGrup)}`,

      );

      const data = await response.json();



      if (data.error) {

        throw new Error(data.error);

      }



      // Update Investasi Card

      updateStat("stat-investasi", data.total_investasi, true);



      // Update Stacking Card

      const dompetCountEl = document.getElementById("stat-dompet-count");

      if (dompetCountEl) {

        dompetCountEl.innerHTML = `${formatKoin(data.jumlah_dompet_stacking)} <span class="unit-text">wallet</span>`;

      }



      const totalKoinEl = document.getElementById("stat-total-koin");

      if (totalKoinEl) {

        totalKoinEl.innerHTML = `${formatKoin(data.total_koin)} <span class="unit-text">XERA</span>`;

      }



      // Simpan ke variabel global untuk airdrop wallet terpilih
      window.selectedWalletAirdrop = data.total_airdrop || 0;
      window.selectedWalletName = namaGrup || "";
      window.selectedWalletAddress = alamatGrup || "";

      // Update tampilan airdrop wallet terpilih di dashboard
      const selectedAirdropXera = document.getElementById("selected-airdrop-xera");
      const selectedAirdropWallet = document.getElementById("selected-airdrop-wallet");
      const cardAirdropSelected = document.getElementById("card-airdrop-selected");

      if (selectedAirdropXera) {
        selectedAirdropXera.textContent = `${formatKoin(window.selectedWalletAirdrop)} XERA`;
      }

      if (selectedAirdropWallet) {
        selectedAirdropWallet.textContent = namaGrup ? namaGrup : "-";
      }

      // Update konversi rupiah & USD berdasarkan harga terkini
      updateSelectedAirdropConversions();

      // Update filter status
      const filterGrupName = namaGrup || currentGroup;
      filterStatus.innerHTML = `✅ <strong>${filterGrupName}</strong> | Jenis: <strong>${currentJenis}</strong> | ${formatKoin(data.jumlah_dompet_stacking)} wallet stacking`;

    } catch (err) {

      console.error("❌ fetchGroupStats error:", err);

      filterStatus.textContent = "❌ Gagal memuat data";

    } finally {

      setLoading(false);

    }

  }

  // Event listeners untuk filter
  if (filterGrup) {
    filterGrup.addEventListener("change", function () {
      const selectedOption = this.options[this.selectedIndex];
      currentGroup = this.value;
      fetchGroupStats();
    });
  }

  if (filterJenis) {
    filterJenis.addEventListener("change", function () {
      currentJenis = this.value;
      fetchGroupStats();
    });
  }

  // Initial load
  if (filterGrup && currentGroup) {
    fetchGroupStats();
  }

})();

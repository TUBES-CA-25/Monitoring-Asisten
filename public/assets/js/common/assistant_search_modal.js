(() => {
  "use strict";

  // Halaman tanpa modal "Cari Asisten" (mis. dashboard belum dimuat) —
  // jangan lakukan apa-apa.
  const modal = document.getElementById("assistantSearchModal");
  if (!modal) return;

  const backdrop = document.getElementById("assistantSearchBackdrop");
  const content = document.getElementById("assistantSearchContent");
  const searchInput = document.getElementById("assistantSearchInput");
  const emptyState = document.getElementById("assistantSearchEmpty");

  let activeJabatan = "all";

  function applyFilters() {
    const query = (searchInput?.value || "").trim().toLowerCase();
    const cards = document.querySelectorAll("#assistantSearchGrid .assistant-search-card");
    let visibleCount = 0;

    cards.forEach((card) => {
      const name = card.dataset.name || "";
      const jabatan = card.dataset.jabatan || "";
      const matchesSearch = !query || name.includes(query);
      const matchesJabatan = activeJabatan === "all" || jabatan === activeJabatan;
      const visible = matchesSearch && matchesJabatan;

      card.classList.toggle("hidden", !visible);
      if (visible) visibleCount++;
    });

    emptyState?.classList.toggle("hidden", visibleCount > 0);
  }

  window.filterAssistantSearch = applyFilters;

  window.filterAssistantJabatan = function (jabatan) {
    activeJabatan = jabatan;

    document.querySelectorAll(".assistant-jabatan-btn").forEach((btn) => {
      btn.classList.toggle("active", btn.dataset.jabatan === jabatan);
    });

    applyFilters();
  };

  window.openAssistantSearchModal = function () {
    modal.classList.remove("hidden");

    setTimeout(() => {
      backdrop?.classList.remove("opacity-0");
      content?.classList.remove("scale-95", "opacity-0");
      content?.classList.add("scale-100", "opacity-100");
    }, 10);

    searchInput?.focus();
  };

  window.closeAssistantSearchModal = function () {
    backdrop?.classList.add("opacity-0");
    content?.classList.remove("scale-100", "opacity-100");
    content?.classList.add("scale-95", "opacity-0");

    setTimeout(() => modal.classList.add("hidden"), 300);
  };

  // Tutup modal dengan tombol Escape (kemudahan akses)
  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape" && !modal.classList.contains("hidden")) {
      window.closeAssistantSearchModal();
    }
  });
})();

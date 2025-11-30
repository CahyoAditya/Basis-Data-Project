</div> </div> </div> <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
    // Ambil elemen
    var wrapper = document.getElementById("wrapper");
    var toggleButton = document.getElementById("sidebarToggle");

    // Apakah user sebelumnya menutup sidebar? Jika ya, langsung tambahkan class 'toggled'
    if (localStorage.getItem("sidebar-status") === "closed") {
        wrapper.classList.add("toggled");
    }

    // Kalo klik tombol
    toggleButton.onclick = function (e) {
        e.preventDefault();
        
        // Toggle class (Buka/Tutup)
        wrapper.classList.toggle("toggled");

        // Simpan status terbaru ke LocalStorage
        if (wrapper.classList.contains("toggled")) {
            localStorage.setItem("sidebar-status", "closed");
        } else {
            localStorage.setItem("sidebar-status", "open");
        }
    };
</script>

</body>
</html>

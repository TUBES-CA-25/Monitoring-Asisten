</main> <footer class="bg-white border-t border-gray-200 py-6 px-8 mt-auto shrink-0 z-10 relative">
        <div class="flex flex-col md:flex-row justify-between text-center items-center gap-4">
            <p class="text-xs text-gray-400 font-medium">
                &copy; <?= date('Y') ?> Integrated Computer Laboratory System FIKOM UMI. All rights reserved.
            </p>
            <!-- <div class="flex gap-6 text-xs text-gray-400 font-medium">
                <a href="#" class="hover:text-blue-600 transition">Bantuan</a>
                <a href="#" class="hover:text-blue-600 transition">Privasi</a>
                <a href="#" class="hover:text-blue-600 transition">Syarat & Ketentuan</a>
            </div> -->
        </div>
    </footer>

</div> <script>
    // Toggle Mobile Sidebar
    const btnMobile = document.querySelector('header button');
    const sidebar = document.querySelector('aside');
    if(btnMobile && sidebar) {
        btnMobile.addEventListener('click', () => {
            sidebar.classList.toggle('-translate-x-full');
        });
    }
</script>

</body>
</html>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <style>
        .nav-switch {
            transition: all 0.3s ease-in-out;
        }
    </style>
</head>

<body class="bg-gray-100 text-gray-800 p-6">
    <!-- Navbar -->
    <?php
    $current_page = basename($_SERVER['PHP_SELF']);
    ?>

    <div class="bg-teal-400 px-6 py-4 rounded-b-xl shadow-lg flex items-center justify-between mb-8">
        <div class="flex space-x-4 bg-gray-900 px-4 py-2 rounded-full shadow-md">
            <a href="dashboard.php" class="text-white px-4 py-2 rounded-full hover:bg-purple-500 <?= $current_page === 'dashboard.php' ? 'bg-purple-600 font-bold' : '' ?>">Dashboard</a>
            <a href="produk.php" class="text-white px-4 py-2 rounded-full hover:bg-purple-500 <?= $current_page === 'produk.php' ? 'bg-purple-600 font-bold' : '' ?>">Stok</a>
            <a href="Transaksi.php" class="text-white px-4 py-2 rounded-full hover:bg-purple-500 <?= $current_page === 'Transaksi.php' ? 'bg-purple-600 font-bold' : '' ?>">Transaksi</a>
        </div>
        <img src="WhatsApp Image 2025-03-18 at 20.09.07.jpeg" alt="Logo Parfumku" class="w-12 h-12 object-contain rounded-full shadow-lg" />
    </div>

    <script>
        const navButtons = document.querySelectorAll('button');
        const navIndicator = document.getElementById('navIndicator');

        function navigate(page) {
            navButtons.forEach(btn => btn.classList.remove('text-purple-300'));
            const btn = Array.from(navButtons).find(b => b.textContent.toLowerCase() === page);
            if (btn) {
                const rect = btn.getBoundingClientRect();
                const navRect = btn.parentElement.getBoundingClientRect();
                navIndicator.style.width = `${rect.width}px`;
                navIndicator.style.height = `${rect.height}px`;
                navIndicator.style.transform = `translateX(${rect.left - navRect.left}px)`;
                btn.classList.add('text-purple-300');

            }
        }
        window.onload = () => navigate('produk');
    </script>
</body>

</html>
<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
    <h2 class="text-xl font-bold mb-4 text-center">Ambil Foto Selfie</h2>
    
    <div id="my_camera" class="w-full h-64 bg-gray-200 rounded-lg overflow-hidden"></div>
    <div id="result" class="hidden"></div>
    
    <input type="hidden" id="photo_data" name="photo">
    <input type="hidden" id="attendance_type" value="<?= htmlspecialchars($_GET['type'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <div class="mt-4 flex justify-center gap-4">
        <button onclick="takeSnapshot()" class="bg-blue-600 text-white px-6 py-2 rounded-full font-bold hover:bg-blue-700">
            <i class="fas fa-camera"></i> Ambil Foto
        </button>
        <button onclick="submitAttendance()" id="btn-submit" class="hidden bg-green-600 text-white px-6 py-2 rounded-full font-bold hover:bg-green-700">
            Kirim Absen
        </button>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/webcamjs/1.0.26/webcam.min.js"></script>
<script src="<?= ASSET_URL ?>/js/user/capture.js"></script>
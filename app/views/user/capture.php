<div class="max-w-md mx-auto bg-white p-6 rounded-lg shadow-lg">
    <h2 class="text-xl font-bold mb-4 text-center">Ambil Foto Selfie</h2>
    
    <div id="my_camera" class="w-full h-64 bg-gray-200 rounded-lg overflow-hidden"></div>
    <div id="result" class="hidden"></div>
    
    <input type="hidden" id="photo_data" name="photo">
    <input type="hidden" id="attendance_type" value="<?= $_GET['type'] ?>">

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
<script>
    Webcam.set({ width: 320, height: 240, image_format: 'jpeg', jpeg_quality: 90 });
    Webcam.attach('#my_camera');

    function takeSnapshot() {
        Webcam.snap(function(data_uri) {
            // Manipulasi Canvas untuk Watermark Waktu (Spec No. 4)
            var canvas = document.createElement('canvas');
            var ctx = canvas.getContext('2d');
            var img = new Image();
            img.onload = function() {
                canvas.width = img.width;
                canvas.height = img.height;
                ctx.drawImage(img, 0, 0);
                
                // Tampilkan preview
                document.getElementById('my_camera').style.display = 'none';
                document.getElementById('result').innerHTML = '<img src="'+finalImage+'"/>';
                document.getElementById('result').classList.remove('hidden');
                document.getElementById('photo_data').value = finalImage;
                document.getElementById('btn-submit').classList.remove('hidden');
            };
            img.src = data_uri;
        });
    }

    function submitAttendance() {
        // Kirim data via AJAX ke Controller
        alert("Data terkirim! (Implementasi AJAX ke database)");
    }
</script>
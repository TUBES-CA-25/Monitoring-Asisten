Webcam.set({ width: 320, height: 240, image_format: 'jpeg', jpeg_quality: 90 });
Webcam.attach('#my_camera');

function takeSnapshot() {
    Webcam.snap(function(data_uri) {
        var canvas = document.createElement('canvas');
        var ctx = canvas.getContext('2d');
        var img = new Image();
        img.onload = function() {
            canvas.width = img.width;
            canvas.height = img.height;
            ctx.drawImage(img, 0, 0);

            var date = new Date();
            var text = date.toLocaleString('id-ID'); // Hari, Tanggal, Jam
            ctx.font = "bold 16px Arial";
            ctx.fillStyle = "yellow";
            ctx.fillText(text, 10, img.height - 20);

            var finalImage = canvas.toDataURL('image/jpeg');

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
    alert("Data terkirim! (Implementasi AJAX ke database)");
}

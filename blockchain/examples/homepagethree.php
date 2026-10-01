<!DOCTYPE html>
<html>
<head>
  <title>Simulated 1000 Bill</title>
  <style>
    body { margin: 0; background: #eee; }
    canvas { display: block; margin: 20px auto; border: 2px solid #333; }
  </style>
</head>
<body>
  <canvas id="bill" width="1000" height="500"></canvas>
  <script>
    const canvas = document.getElementById("bill");
    const ctx = canvas.getContext("2d");
    const width = canvas.width;
    const height = canvas.height;

    // Background gradient with subtle texture
    const gradient = ctx.createLinearGradient(0, 0, width, height);
    gradient.addColorStop(0, "#f5e6d3");
    gradient.addColorStop(0.5, "#e0d0b0");
    gradient.addColorStop(1, "#d9c3a3");
    ctx.fillStyle = gradient;
    ctx.fillRect(0, 0, width, height);

    // Guilloche pattern generator
    function drawGuilloche(cx, cy, radius, loops, color) {
      ctx.strokeStyle = color;
      ctx.lineWidth = 0.5;
      ctx.beginPath();
      for (let i = 0; i <= 360 * loops; i++) {
        const angle = (i * Math.PI) / 180;
        const r = radius + Math.sin(angle * 5) * 10;
        const x = cx + r * Math.cos(angle);
        const y = cy + r * Math.sin(angle);
        ctx.lineTo(x, y);
      }
      ctx.stroke();
    }

    // Decorative guilloche circles
    drawGuilloche(width/2, height/2, 180, 6, "#006400");
    drawGuilloche(width/2, height/2, 140, 5, "#800000");

    // Border embroidery lines
    function drawBorder() {
      ctx.strokeStyle = "#004d00";
      ctx.lineWidth = 1;
      for (let y = 40; y < height - 40; y += 8) {
        ctx.beginPath();
        ctx.moveTo(40, y);
        ctx.lineTo(60 + Math.sin(y/10)*5, y);
        ctx.stroke();

        ctx.beginPath();
        ctx.moveTo(width-40, y);
        ctx.lineTo(width-60 + Math.sin(y/10)*5, y);
        ctx.stroke();
      }
    }
    drawBorder();

    // Watermark-style imagery
    ctx.globalAlpha = 0.05;
    ctx.font = "bold 180px serif";
    ctx.fillStyle = "#333";
    ctx.fillText("KENYA", width/4, height/2);
    ctx.globalAlpha = 1;

    // Denomination text
    ctx.font = "bold 100px serif";
    ctx.fillStyle = "#800000";
    ctx.fillText("1000", width - 300, height - 80);

    // Authority text
    ctx.font = "24px sans-serif";
    ctx.fillStyle = "#333";
    ctx.fillText("Central Bank of Kenya", 60, height - 50);

    // Microtext line (hidden detail)
    ctx.font = "8px sans-serif";
    ctx.fillStyle = "#006400";
    for (let i = 0; i < width-120; i+=60) {
      ctx.fillText("Authenticity • Integrity • Excellence", 60+i, 70);
    }
  </script>
</body>
</html>

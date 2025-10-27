<?php
session_start();
if(!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
require_once '../db/conexao.php';

// Consulta para buscar todos os astros
$sql = "SELECT id, nome, posicao_x, posicao_y, tipo, tamanho, cor, descricao, recursos FROM astros ORDER BY posicao_x, posicao_y";
$resultado = $conexao->query($sql);

$astros = [];
if ($resultado->num_rows > 0) {
    while ($row = $resultado->fetch_assoc()) {
        // Escala de 100000px para 1000px
        $row['posicao_x'] = $row['posicao_x'] / 100;
        $row['posicao_y'] = $row['posicao_y'] / 100;
        $astros[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mapa do Sistema Solar</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #0f0c29, #302b63, #24243e);
            color: #fff;
            min-height: 100vh;
            overflow: hidden;
        }

        .container {
            width: 100%;
            height: 100vh;
            position: relative;
            overflow: hidden;
        }

        .map-container {
            position: relative;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, #1a1a2e 0%, #0f0f1e 100%);
            overflow: auto;
        }

        .map-grid {
            position: absolute;
            width: 2000px;
            height: 1000px;
            background-image: 
                linear-gradient(rgba(58, 123, 213, 0.1) 1px, transparent 1px),
                linear-gradient(90deg, rgba(58, 123, 213, 0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            transform-origin: center;
        }

        .astro {
            position: absolute;
            border-radius: 50%;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 0 20px rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            text-shadow: 0 0 5px rgba(0, 0, 0, 0.8);
        }

        .astro:hover {
            transform: scale(1.2);
            box-shadow: 0 0 30px rgba(255, 255, 255, 0.6);
            z-index: 10;
        }

        .astro.estrela {
            background: radial-gradient(circle, #FFD700, #FFA500);
            box-shadow: 0 0 50px #FFD700, 0 0 100px #FFA500;
        }

        .astro.planeta {
            background: radial-gradient(circle, #4169E1, #1E90FF);
        }

        .astro.lua {
            background: radial-gradient(circle, #C0C0C0, #A9A9A9);
        }

        .astro.planeta_anao {
            background: radial-gradient(circle, #D2B48C, #A0522D);
        }

        .info-panel {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 300px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 10px;
            padding: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.3s ease;
            transform: translateX(320px);
        }

        .info-panel.active {
            transform: translateX(0);
        }

        .info-panel h2 {
            color: #00d2ff;
            margin-bottom: 10px;
        }

        .info-panel p {
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-panel .label {
            color: #aaa;
            display: inline-block;
            width: 80px;
        }

        .close-btn {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            color: #fff;
            font-size: 20px;
            cursor: pointer;
        }

        .controls {
            position: fixed;
            bottom: 20px;
            left: 20px;
            display: flex;
            gap: 10px;
        }

        .controls button {
            padding: 10px 15px;
            background: rgba(0, 0, 0, 0.5);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .controls button:hover {
            background: rgba(0, 210, 255, 0.3);
        }

        .legend {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(0, 0, 0, 0.7);
            border-radius: 10px;
            padding: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .legend h3 {
            color: #00d2ff;
            margin-bottom: 10px;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin-bottom: 5px;
        }

        .legend-color {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="map-container">
            <div class="map-grid" id="mapGrid">
                <?php foreach ($astros as $astro): ?>
                    <div class="astro <?php echo $astro['tipo']; ?>" 
                         style="left: <?php echo $astro['posicao_x']; ?>px; 
                                top: <?php echo $astro['posicao_y']; ?>px; 
                                width: <?php echo $astro['tamanho']; ?>px; 
                                height: <?php echo $astro['tamanho']; ?>px;
                                background-color: <?php echo $astro['cor']; ?>;"
                         data-id="<?php echo $astro['id']; ?>"
                         data-nome="<?php echo $astro['nome']; ?>"
                         data-tipo="<?php echo $astro['tipo']; ?>"
                         data-descricao="<?php echo $astro['descricao']; ?>"
                         data-recursos="<?php echo $astro['recursos']; ?>">
                        <?php echo $astro['nome']; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="legend">
            <h3>Legenda</h3>
            <div class="legend-item">
                <div class="legend-color" style="background: radial-gradient(circle, #FFD700, #FFA500);"></div>
                <span>Estrela</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: radial-gradient(circle, #4169E1, #1E90FF);"></div>
                <span>Planeta</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: radial-gradient(circle, #C0C0C0, #A9A9A9);"></div>
                <span>Lua</span>
            </div>
            <div class="legend-item">
                <div class="legend-color" style="background: radial-gradient(circle, #D2B48C, #A0522D);"></div>
                <span>Planeta Anão</span>
            </div>
        </div>

        <div class="info-panel" id="infoPanel">
            <button class="close-btn" id="closeBtn">&times;</button>
            <h2 id="infoNome">Nome do Astro</h2>
            <p><span class="label">Tipo:</span> <span id="infoTipo"></span></p>
            <p><span class="label">Descrição:</span> <span id="infoDescricao"></span></p>
            <p><span class="label">Recursos:</span> <span id="infoRecursos"></span></p>
        </div>

        <div class="controls">
            <button id="zoomIn">Zoom +</button>
            <button id="zoomOut">Zoom -</button>
            <button id="resetZoom">Resetar Zoom</button>
        </div>
    </div>

    <script>
        const mapGrid = document.getElementById('mapGrid');
        const infoPanel = document.getElementById('infoPanel');
        const closeBtn = document.getElementById('closeBtn');
        const infoNome = document.getElementById('infoNome');
        const infoTipo = document.getElementById('infoTipo');
        const infoDescricao = document.getElementById('infoDescricao');
        const infoRecursos = document.getElementById('infoRecursos');
        const zoomInBtn = document.getElementById('zoomIn');
        const zoomOutBtn = document.getElementById('zoomOut');
        const resetZoomBtn = document.getElementById('resetZoom');

        let currentZoom = 1;
        const zoomStep = 0.2;

        document.querySelectorAll('.astro').forEach(astro => {
            astro.addEventListener('click', function() {
                const nome = this.getAttribute('data-nome');
                const tipo = this.getAttribute('data-tipo');
                const descricao = this.getAttribute('data-descricao');
                const recursos = this.getAttribute('data-recursos');

                infoNome.textContent = nome;
                infoTipo.textContent = tipo;
                infoDescricao.textContent = descricao;
                
                try {
                    const recursosObj = JSON.parse(recursos);
                    let recursosStr = '';
                    for (const [key, value] of Object.entries(recursosObj)) {
                        recursosStr += `${key}: ${value}% `;
                    }
                    infoRecursos.textContent = recursosStr;
                } catch (e) {
                    infoRecursos.textContent = recursos;
                }

                infoPanel.classList.add('active');
            });
        });

        closeBtn.addEventListener('click', function() {
            infoPanel.classList.remove('active');
        });

        zoomInBtn.addEventListener('click', function() {
            currentZoom += zoomStep;
            if (currentZoom > 3) currentZoom = 3;
            mapGrid.style.transform = `scale(${currentZoom})`;
        });

        zoomOutBtn.addEventListener('click', function() {
            currentZoom -= zoomStep;
            if (currentZoom < 0.5) currentZoom = 0.5;
            mapGrid.style.transform = `scale(${currentZoom})`;
        });

        resetZoomBtn.addEventListener('click', function() {
            currentZoom = 1;
            mapGrid.style.transform = `scale(${currentZoom})`;
        });

        window.addEventListener('load', function() {
            const sol = document.querySelector('.astro.estrela');
            if (sol) {
                const solX = sol.offsetLeft + sol.offsetWidth / 2;
                const solY = sol.offsetTop + sol.offsetHeight / 2;
                const container = document.querySelector('.map-container');
                container.scrollLeft = solX - container.offsetWidth / 2;
                container.scrollTop = solY - container.offsetHeight / 2;
            }
        });
    </script>
</body>
</html>

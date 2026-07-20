<style>
/* ── Curva superior ── */
.footer-wave {
    display: block;
    line-height: 0;
    margin-bottom: -2px;
}
.footer-wave svg {
    display: block;
    width: 100%;
    height: 50px;
}
.footer-wave path { fill: #c62828; }

/* ── Footer ── */
.footer {
    background: #c62828;
    color: white;
    padding: 20px 0 14px;
    position: relative;
    overflow: hidden;
    font-family: 'Inter', Arial, sans-serif;
}

/* Patrón de puntos */
.footer::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image: radial-gradient(rgba(0,0,0,.12) 1px, transparent 1px);
    background-size: 8px 8px;
    opacity: .5;
    pointer-events: none;
}

.footer-container {
    position: relative;
    z-index: 2;
    width: 90%;
    max-width: 1200px;
    margin: auto;
    display: flex;
    justify-content: space-between;
    gap: 20px;
    flex-wrap: wrap;
}

.footer-column { flex: 1; min-width: 160px; }

.footer-column h3 {
    font-size: 13px;
    font-weight: 800;
    margin-bottom: 8px;
    letter-spacing: 0.3px;
}

.footer-column p,
.footer-column li {
    margin-bottom: 3px;
    color: rgba(255,255,255,0.88);
    font-size: 11px;
    line-height: 1.4;
}

.footer-column ul {
    list-style: none;
    padding: 0;
    margin: 0;
}

.footer-column a {
    color: rgba(255,255,255,0.88);
    text-decoration: none;
    font-size: 11px;
    transition: color 0.2s;
}
.footer-column a:hover { color: #ffcc00; }

.logo-column {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
}
.logo-column img {
    width: 110px;
}

/* Redes sociales */
.footer-social {
    display: flex;
    gap: 10px;
    align-items: center;
}
.footer-social a {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,0.15);
    color: white;
    font-size: 14px;
    transition: background 0.2s, transform 0.2s;
    text-decoration: none;
}
.footer-social a:hover {
    background: rgba(255,255,255,0.35);
    transform: translateY(-3px);
}

/* Línea inferior */
.footer-bottom {
    position: relative;
    z-index: 2;
    width: 90%;
    max-width: 1200px;
    margin: 12px auto 0;
    padding-top: 10px;
    border-top: 1px solid rgba(255,255,255,0.2);
    text-align: center;
    font-size: 11px;
    color: rgba(255,255,255,0.7);
}

@media (max-width: 768px) {
    .footer { padding: 16px 0 12px; }
    .footer-wave svg { height: 30px; }

    .footer-container {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 16px;
        text-align: left;
    }

    /* Logo y redes — primera fila, centrado */
    .logo-column {
        grid-column: 1 / -1;
        order: -1;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding-bottom: 10px;
        border-bottom: 1px solid rgba(255,255,255,0.15);
    }
    .logo-column img { width: 70px; }
    .footer-social a  { width: 28px; height: 28px; font-size: 12px; }

    .footer-column h3  { font-size: 11px; margin-bottom: 6px; }
    .footer-column p,
    .footer-column li,
    .footer-column a   { font-size: 10px; }

    .footer-bottom { font-size: 10px; margin-top: 8px; padding-top: 8px; }
}
</style>

<!-- Curva superior -->
<div class="footer-wave">
    <svg viewBox="0 0 1440 120" preserveAspectRatio="none">
        <path d="M0,0 C320,110 1120,110 1440,0 L1440,120 L0,120 Z"></path>
    </svg>
</div>

<footer class="footer">
    <div class="footer-container">

        <div class="footer-column">
            <h3>Dirección</h3>
            <p>Santa Cruz de la Sierra, Bolivia</p>
            <p>Línea de atención:</p>
            <p>+591 70 000 000</p>
            <p>Email:</p>
            <p>contacto@polloelsolar.com</p>
        </div>

        <div class="footer-column">
            <h3>Contacto</h3>
            <ul>
                <li><a href="contacto.php">Contáctanos</a></li>
                <li><a href="sucursal.php">Nuestras Sucursales</a></li>
                <li><a href="menu.php">Ver Menú</a></li>
                <li><a href="../index.html">Página Principal</a></li>
            </ul>
        </div>

        <div class="footer-column logo-column">
            <img src="img/logito.png" alt="Pollo El Solar">
            <div class="footer-social">
                <a href="https://www.facebook.com/polloelsolar" target="_blank" aria-label="Facebook"><i class="fab fa-facebook-f"></i></a>
                <a href="https://www.instagram.com/polloelsolar" target="_blank" aria-label="Instagram"><i class="fab fa-instagram"></i></a>
                <a href="https://www.tiktok.com/@papasolar" target="_blank" aria-label="TikTok"><i class="fab fa-tiktok"></i></a>
                <a href="https://wa.link/njstgf" target="_blank" aria-label="WhatsApp"><i class="fab fa-whatsapp"></i></a>
            </div>
        </div>

    </div>

    <div class="footer-bottom">
        © <?php echo date('Y'); ?> Pollo El Solar — Todos los derechos reservados
    </div>
</footer>

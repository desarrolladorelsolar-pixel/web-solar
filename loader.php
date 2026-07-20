<style>
/* ── Loader ── */
#loader {
    position: fixed;
    top: 0; left: 0;
    width: 100vw;
    height: 100vh;
    background-color: #1a1a1a;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 100000;
    overflow: hidden;
    touch-action: none;
    transition: opacity 0.5s ease, visibility 0.5s ease;
}

.loader-bg {
    position: absolute;
    width: 100%;
    height: 100%;
    object-fit: cover;
    opacity: 0.2;
    pointer-events: none;
}

.loader-chicken {
    position: relative;
    width: 60vw;
    max-width: 700px;
    height: auto;
    z-index: 2;
    animation: loaderSpin 1s cubic-bezier(0.68, -0.55, 0.265, 1.55) infinite;
}

#loader.hide {
    opacity: 0;
    visibility: hidden;
    pointer-events: none;
}

@keyframes loaderSpin {
    from { transform: rotate(0deg); }
    to   { transform: rotate(360deg); }
}
</style>

<div id="loader">
    <img src="../combos/fr.png"              class="loader-bg"      alt="">
    <img src="../combos/piernasinfondo.png"  class="loader-chicken" alt="Cargando...">
</div>

<script>
window.addEventListener('load', function() {
    setTimeout(function() {
        const loader = document.getElementById('loader');
        if (loader) loader.classList.add('hide');
    }, 1000);
});
</script>

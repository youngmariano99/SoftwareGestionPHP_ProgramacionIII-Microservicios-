</div> <script>
        document.addEventListener('DOMContentLoaded', () => {
            const token = localStorage.getItem('jwt_token');
            if (!token) {
                window.location.href = 'index.php';
                return;
            }
            
            try {
                // Decodificar token para mostrar nombre
                const payload = JSON.parse(atob(token.split('.')[1]));
                document.getElementById('nombreUsuario').textContent = payload.data.nombre + ' (' + payload.data.rol + ')';
            } catch(e) {
                console.error("Error token", e);
            }

            const btnLogout = document.getElementById('logout');
            if(btnLogout) {
                btnLogout.addEventListener('click', () => {
                    localStorage.removeItem('jwt_token');
                    window.location.href = 'index.php';
                });
            }
        });
    </script>

    <?php if(isset($seccion) && $seccion == 'inicio'): ?>
        <script src="js/dashboard_inicio.js"></script>
    <?php endif; ?>

    <?php if(isset($seccion) && $seccion == 'sucursales'): ?>
        <script src="js/dashboard_sucursales.js"></script>
    <?php endif; ?>

    <?php if(isset($seccion) && $seccion == 'empleados'): ?>
        <script src="js/dashboard_empleados.js"></script>
    <?php endif; ?>

    <?php if(isset($seccion) && $seccion == 'logs'): ?>
        <script src="js/dashboard_logs.js"></script>
    <?php endif; ?>

    <?php if(isset($seccion) && $seccion == 'productos'): ?>
        <script src="js/dashboard_productos.js"></script>
    <?php endif; ?>

    <?php if(isset($seccion) && $seccion == 'ventas'): ?>
        <script src="js/dashboard_ventas.js"></script>
    <?php endif; ?>

    </body>
</html>
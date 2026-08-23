document.addEventListener("DOMContentLoaded", function () {

    const buscador = document.getElementById("buscar");
    const tabla = document.getElementById("tablaRecursos");

    if (!buscador || !tabla) {
        return;
    }

    buscador.addEventListener("input", function () {

        const texto = buscador.value.toLowerCase().trim();

        const filas = tabla.querySelectorAll("tbody tr");

        filas.forEach(function (fila) {

            const contenido = fila.textContent.toLowerCase();

            if (contenido.includes(texto)) {

                fila.style.display = "";

            } else {

                fila.style.display = "none";

            }

        });

    });

});

// =====================================================
// MOSTRAR / OCULTAR ESPECIFICACIONES DE LAPTOP
// =====================================================

document.addEventListener("DOMContentLoaded", function () {

    const categoria =
        document.getElementById("categoria_id");

    const seccionTecnica =
        document.getElementById("seccionTecnica");


    if (!categoria || !seccionTecnica) {
        return;
    }


    function actualizarEspecificaciones() {

        const opcion =
            categoria.options[categoria.selectedIndex];


        const nombreCategoria =
            opcion
                ? opcion.textContent
                    .trim()
                    .toLowerCase()
                : "";


        const esLaptop =
            nombreCategoria.includes("laptop");


        if (esLaptop) {

            seccionTecnica.style.display = "";

        } else {

            seccionTecnica.style.display = "none";

        }

    }


    categoria.addEventListener(
        "change",
        actualizarEspecificaciones
    );


    actualizarEspecificaciones();

});
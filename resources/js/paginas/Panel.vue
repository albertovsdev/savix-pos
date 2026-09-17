<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    usuario: { type: Object, required: true },
    negocio: { type: Object, default: null },
    sucursal: { type: Object, default: null },
    modulos: { type: Array, default: () => [] },
    puede_administrar: { type: Boolean, default: false },
});

const formulario = useForm({});
const cerrarSesion = () => formulario.delete('/acceso');
</script>

<template>
    <Head title="Panel" />

    <main class="savix-pos-panel">
        <header class="savix-pos-panel__encabezado">
            <div class="savix-pos-marca">
                <span class="savix-pos-marca__sello">SI</span>
                <div>
                    <p class="savix-pos-marca__nombre">{{ negocio?.nombre_comercial || 'SAVIX POS' }}</p>
                    <p class="savix-pos-marca__subtitulo">{{ sucursal?.nombre || 'Sin sucursal asignada' }}</p>
                </div>
            </div>
            <div class="savix-pos-panel__usuario">
                <span>{{ usuario.nombre }}</span>
                <Link v-if="puede_administrar" class="savix-pos-boton savix-pos-boton--secundario" href="/administracion">Administración</Link>
                <button class="savix-pos-boton savix-pos-boton--secundario" @click="cerrarSesion">Salir</button>
            </div>
        </header>

        <section class="savix-pos-panel__contenido">
            <p class="savix-pos-eyebrow">INSTALACIÓN LISTA</p>
            <h1>La base de tu operación está preparada.</h1>
            <p class="savix-pos-texto-secundario">Configura usuarios, permisos y sucursales antes de empezar a capturar el catálogo de productos.</p>

            <div class="savix-pos-panel__modulos">
                <article v-for="modulo in modulos" :key="modulo" class="savix-pos-modulo">
                    <span class="savix-pos-modulo__punto"></span>
                    <span>{{ modulo }}</span>
                </article>
            </div>
        </section>
    </main>
</template>

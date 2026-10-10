<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    usuario: { type: Object, required: true },
    negocio: { type: Object, default: null },
    sucursal: { type: Object, default: null },
    modulos: { type: Array, default: () => [] },
    puede_administrar: { type: Boolean, default: false },
    puede_catalogos: { type: Boolean, default: false },
    puede_operar: { type: Boolean, default: false },
    puede_preparar: { type: Boolean, default: false },
    puede_cobrar: { type: Boolean, default: false },
    puede_ver_inventario: { type: Boolean, default: false },
    puede_ver_compras: { type: Boolean, default: false },
    puede_ver_reportes: { type: Boolean, default: false },
});

const formulario = useForm({});
const cerrarSesion = () => formulario.delete('/acceso');

const accesos = computed(() => [
    { habilitado: props.puede_operar, nombre: 'Operación', descripcion: 'Mesas, mostrador y rondas activas.', enlace: '/operacion', clave: 'operacion' },
    { habilitado: props.puede_preparar, nombre: 'Preparación', descripcion: 'Cola de cocina, barra y áreas.', enlace: '/preparacion', clave: 'preparacion' },
    { habilitado: props.puede_cobrar, nombre: 'Caja', descripcion: 'Cuentas abiertas, cobros y tickets.', enlace: '/caja', clave: 'caja' },
    { habilitado: props.puede_ver_inventario, nombre: 'Inventario', descripcion: 'Existencias, ajustes y movimientos.', enlace: '/inventario', clave: 'inventario' },
    { habilitado: props.puede_ver_compras, nombre: 'Compras', descripcion: 'Proveedores y entradas de mercancía.', enlace: '/compras', clave: 'compras' },
    { habilitado: props.puede_ver_reportes, nombre: 'Reportes', descripcion: 'Ventas, pagos y alertas por periodo.', enlace: '/reportes', clave: 'reportes' },
    { habilitado: props.puede_catalogos, nombre: 'Catálogo', descripcion: 'Productos, recetas y disponibilidad.', enlace: '/catalogos', clave: 'catalogos' },
    { habilitado: props.puede_administrar, nombre: 'Administración', descripcion: 'Equipo, sucursales y configuración.', enlace: '/administracion', clave: 'administracion' },
].filter((acceso) => acceso.habilitado));
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
                <Link v-if="puede_operar" class="savix-pos-boton savix-pos-boton--secundario" href="/operacion">Operación</Link>
<Link v-if="puede_preparar" class="savix-pos-boton savix-pos-boton--secundario" href="/preparacion">Preparación</Link>
<Link v-if="puede_cobrar" class="savix-pos-boton savix-pos-boton--secundario" href="/caja">Caja</Link>
                <Link v-if="puede_ver_inventario" class="savix-pos-boton savix-pos-boton--secundario" href="/inventario">Inventario</Link>
                <Link v-if="puede_ver_compras" class="savix-pos-boton savix-pos-boton--secundario" href="/compras">Compras</Link>
                <Link v-if="puede_ver_reportes" class="savix-pos-boton savix-pos-boton--secundario" href="/reportes">Reportes</Link>
                <Link v-if="puede_catalogos" class="savix-pos-boton savix-pos-boton--secundario" href="/catalogos">Catálogo</Link>
                <Link v-if="puede_administrar" class="savix-pos-boton savix-pos-boton--secundario" href="/administracion">Administración</Link>
                <button class="savix-pos-boton savix-pos-boton--secundario" @click="cerrarSesion">Salir</button>
            </div>
        </header>

        <section class="savix-pos-panel__contenido">
            <p class="savix-pos-eyebrow">INSTALACIÓN LISTA</p>
            <h1>La base de tu operación está preparada.</h1>
            <p class="savix-pos-texto-secundario">Configura usuarios, permisos y sucursales antes de empezar a capturar el catálogo de productos.</p>

            <nav v-if="accesos.length" class="savix-pos-panel__accesos" aria-label="Módulos disponibles">
                <Link v-for="acceso in accesos" :key="acceso.clave" class="savix-pos-acceso-modulo" :href="acceso.enlace">
                    <span class="savix-pos-acceso-modulo__marca" aria-hidden="true">{{ acceso.nombre.slice(0, 1) }}</span>
                    <span class="savix-pos-acceso-modulo__contenido"><strong>{{ acceso.nombre }}</strong><small>{{ acceso.descripcion }}</small></span>
                    <span class="savix-pos-acceso-modulo__flecha" aria-hidden="true">→</span>
                </Link>
            </nav>
            <p v-else class="savix-pos-vacio">Tu cuenta no tiene módulos asignados. Solicita acceso a una persona administradora.</p>

            <div v-if="modulos.length" class="savix-pos-panel__modulos" aria-label="Módulos activos de la sucursal">
                <article v-for="modulo in modulos" :key="modulo" class="savix-pos-modulo">
                    <span class="savix-pos-modulo__punto"></span>
                    <span>{{ modulo }}</span>
                </article>
            </div>
        </section>
    </main>
</template>

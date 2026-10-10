<script setup>
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    sucursal_seleccionada: { type: Object, required: true }, sucursales: { type: Array, default: () => [] },
    areas: { type: Array, default: () => [] }, area_seleccionada: { type: Object, default: null },
    detalles: { type: Array, default: () => [] }, puede_actualizar: { type: Boolean, default: false },
});

const mensaje = computed(() => usePage().props.flash?.exito);
const siguienteEstado = { pendiente: 'en_preparacion', en_preparacion: 'listo', listo: 'entregado' };
const etiquetaAccion = { pendiente: 'Iniciar preparación', en_preparacion: 'Marcar listo', listo: 'Entregar' };
const etiquetaEstado = { pendiente: 'Pendiente', en_preparacion: 'En preparación', listo: 'Listo' };
const cambiarSucursal = (evento) => router.get('/preparacion', { ref_sucursal: evento.target.value }, { preserveState: false, preserveScroll: true });
const cambiarArea = (evento) => router.get('/preparacion', { ref_sucursal: props.sucursal_seleccionada.id_sucursal, ref_area_preparacion: evento.target.value }, { preserveState: false, preserveScroll: true });
const avanzar = (detalle) => router.post(`/preparacion/detalles/${detalle.id_detalle_ronda_pedido_area_preparacion}/estado`, {
    ref_sucursal: props.sucursal_seleccionada.id_sucursal,
    estado_preparacion: siguienteEstado[detalle.estado_preparacion],
}, { preserveScroll: true });
const referenciaPedido = (detalle) => detalle.tipo_servicio === 'mesa' ? detalle.mesa : detalle.referencia_mostrador;
</script>

<template>
    <Head title="Preparación" />
    <main class="savix-pos-administracion">
        <header class="savix-pos-administracion__encabezado">
            <div class="savix-pos-marca"><span class="savix-pos-marca__sello">SI</span><div><p class="savix-pos-marca__nombre">Preparación</p><p class="savix-pos-marca__subtitulo">Cocina, barra y áreas configuradas</p></div></div>
            <Link class="savix-pos-boton savix-pos-boton--secundario" href="/panel">Volver al panel</Link>
        </header>

        <section class="savix-pos-administracion__contenido">
            <p v-if="mensaje" class="savix-pos-notificacion" role="status">{{ mensaje }}</p>
            <div class="savix-pos-seccion__titulo"><p class="savix-pos-eyebrow">PREPARACIÓN</p><h1>{{ area_seleccionada ? area_seleccionada.nombre : 'Sin área asignada' }}</h1><p v-if="area_seleccionada">Solo aparecen productos nuevos enviados a esta área.</p><p v-else>Pide a un administrador que te asigne un área de preparación en tu sucursal.</p></div>
            <div class="savix-pos-cuadricula savix-pos-cuadricula--dos">
                <label class="savix-pos-campo"><span>Sucursal</span><select :value="sucursal_seleccionada.id_sucursal" @change="cambiarSucursal"><option v-for="item in sucursales" :key="item.id_sucursal" :value="item.id_sucursal">{{ item.nombre }}</option></select></label>
                <label class="savix-pos-campo"><span>Área</span><select :value="area_seleccionada?.id_area_preparacion || ''" :disabled="!areas.length" @change="cambiarArea"><option v-if="!areas.length" value="">Sin áreas disponibles</option><option v-for="item in areas" :key="item.id_area_preparacion" :value="item.id_area_preparacion">{{ item.nombre }}</option></select></label>
            </div>

            <section v-if="area_seleccionada" class="savix-pos-seccion">
                <div class="savix-pos-seccion__subtitulo"><h2>Por preparar</h2><p>{{ detalles.length }} producto(s) activo(s) en {{ area_seleccionada.nombre }}.</p></div>
                <div class="savix-pos-lista">
                    <article v-for="detalle in detalles" :key="detalle.id_detalle_ronda_pedido_area_preparacion" class="savix-pos-lista__fila savix-pos-preparacion__tarjeta" :class="`savix-pos-preparacion__tarjeta--${detalle.estado_preparacion}`">
                        <div>
                            <strong>{{ detalle.cantidad }} × {{ detalle.nombre_producto }}</strong>
                            <span>{{ referenciaPedido(detalle) }} · Ronda {{ detalle.numero_ronda }} · {{ etiquetaEstado[detalle.estado_preparacion] }}</span>
                            <span v-if="detalle.modificadores.length">{{ detalle.modificadores.join(', ') }}</span>
                            <span v-if="detalle.nota_preparacion">Nota: {{ detalle.nota_preparacion }}</span>
                        </div>
                        <button v-if="puede_actualizar" class="savix-pos-boton savix-pos-boton--primario" type="button" @click="avanzar(detalle)">{{ etiquetaAccion[detalle.estado_preparacion] }}</button>
                    </article>
                    <p v-if="!detalles.length" class="savix-pos-vacio">No hay productos pendientes para esta área.</p>
                </div>
            </section>
        </section>
    </main>
</template>

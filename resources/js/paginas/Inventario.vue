<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    sucursal_seleccionada: { type: Object, required: true },
    sucursales: { type: Array, default: () => [] },
    recursos: { type: Array, default: () => [] },
    movimientos: { type: Array, default: () => [] },
    puede_ajustar: { type: Boolean, default: false },
});

const mensaje = computed(() => usePage().props.flash?.exito);
const recurso_seleccionado = ref(null);
const ajuste = useForm({ existencia_nueva: '', motivo: '', ref_sucursal: props.sucursal_seleccionada.id_sucursal });
const cambiarSucursal = (evento) => router.get('/inventario', { ref_sucursal: evento.target.value }, { preserveState: false, preserveScroll: true });
const formatoCantidad = (valor) => Number(valor || 0).toLocaleString('es-MX', { maximumFractionDigits: 4 });
const etiquetaMovimiento = (tipo) => ({ entrada_inicial: 'Existencia inicial', entrada_ajuste: 'Ajuste manual', salida_venta: 'Salida por venta', entrada_cancelacion: 'Devolución por cancelación', salida_merma: 'Merma' }[tipo] || tipo);
const seleccionarRecurso = (recurso) => {
    recurso_seleccionado.value = recurso;
    ajuste.existencia_nueva = Number(recurso.existencia_actual).toFixed(4);
    ajuste.motivo = '';
    ajuste.clearErrors();
};
const cancelarAjuste = () => { recurso_seleccionado.value = null; ajuste.reset(); ajuste.ref_sucursal = props.sucursal_seleccionada.id_sucursal; };
const guardarAjuste = () => {
    const tipo = recurso_seleccionado.value.tipo_recurso === 'producto' ? 'productos' : 'insumos';
    ajuste.post(`/inventario/${tipo}/${recurso_seleccionado.value.id_recurso}/ajustar`, { preserveScroll: true, onSuccess: cancelarAjuste });
};
</script>

<template>
    <Head title="Inventario" />

    <main class="savix-pos-administracion">
        <header class="savix-pos-administracion__encabezado">
            <div class="savix-pos-marca"><span class="savix-pos-marca__sello">SI</span><div><p class="savix-pos-marca__nombre">Inventario</p><p class="savix-pos-marca__subtitulo">Existencias y movimientos</p></div></div>
            <Link class="savix-pos-boton savix-pos-boton--secundario" href="/panel">Volver al panel</Link>
        </header>

        <section class="savix-pos-administracion__contenido">
            <p v-if="mensaje" class="savix-pos-notificacion" role="status">{{ mensaje }}</p>
            <div class="savix-pos-seccion__titulo"><p class="savix-pos-eyebrow">CONTROL DE EXISTENCIAS</p><h1>Inventario de {{ sucursal_seleccionada.nombre }}</h1><p>Consulta lo disponible, identifica mínimos y registra ajustes con motivo.</p></div>
            <label class="savix-pos-campo savix-pos-campo--selector-sucursal"><span>Sucursal</span><select :value="sucursal_seleccionada.id_sucursal" @change="cambiarSucursal"><option v-for="item in sucursales" :key="item.id_sucursal" :value="item.id_sucursal">{{ item.nombre }}</option></select></label>

            <section class="savix-pos-seccion">
                <div class="savix-pos-seccion__subtitulo"><h2>Existencias controladas</h2><p>Productos por unidad o mixtos e insumos de recetas.</p></div>
                <div class="savix-pos-lista">
                    <article v-for="recurso in recursos" :key="`${recurso.tipo_recurso}-${recurso.id_recurso}`" class="savix-pos-lista__fila">
                        <div><strong>{{ recurso.nombre }}</strong><span>{{ recurso.tipo_recurso === 'producto' ? `Producto · ${recurso.tipo_inventario}` : 'Insumo de receta' }}<span v-if="recurso.codigo"> · {{ recurso.codigo }}</span></span></div>
                        <div><strong>{{ formatoCantidad(recurso.existencia_actual) }} {{ recurso.unidad }}</strong><span :class="recurso.bajo_minimo ? 'savix-pos-error' : ''">Mínimo: {{ formatoCantidad(recurso.existencia_minima) }}</span></div>
                        <button v-if="puede_ajustar" class="savix-pos-boton savix-pos-boton--secundario" type="button" @click="seleccionarRecurso(recurso)">Ajustar</button>
                    </article>
                    <p v-if="!recursos.length" class="savix-pos-vacio">Aún no hay existencias que requieran control en esta sucursal.</p>
                </div>
            </section>

            <form v-if="recurso_seleccionado && puede_ajustar" class="savix-pos-formulario-interno" @submit.prevent="guardarAjuste">
                <div class="savix-pos-seccion__subtitulo"><h2>Ajustar: {{ recurso_seleccionado.nombre }}</h2><p>Existencia actual: {{ formatoCantidad(recurso_seleccionado.existencia_actual) }} {{ recurso_seleccionado.unidad }}. Se registrará solo la diferencia.</p></div>
                <div class="savix-pos-cuadricula savix-pos-cuadricula--dos"><label class="savix-pos-campo"><span>Nueva existencia física</span><input v-model="ajuste.existencia_nueva" type="number" min="0" step="0.0001" required><small v-if="ajuste.errors.existencia_nueva" class="savix-pos-error">{{ ajuste.errors.existencia_nueva }}</small></label><label class="savix-pos-campo"><span>Motivo obligatorio</span><textarea v-model="ajuste.motivo" rows="2" minlength="5" maxlength="450" required></textarea><small v-if="ajuste.errors.motivo" class="savix-pos-error">{{ ajuste.errors.motivo }}</small></label></div>
                <footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--secundario" type="button" @click="cancelarAjuste">Cancelar</button><button class="savix-pos-boton savix-pos-boton--primario" :disabled="ajuste.processing">{{ ajuste.processing ? 'Guardando…' : 'Guardar ajuste' }}</button></footer>
            </form>

            <section class="savix-pos-seccion">
                <div class="savix-pos-seccion__subtitulo"><h2>Últimos movimientos</h2><p>Bitácora de las últimas 60 entradas, salidas y ajustes.</p></div>
                <div class="savix-pos-lista">
                    <article v-for="movimiento in movimientos" :key="movimiento.id_movimiento_inventario" class="savix-pos-lista__fila"><div><strong>{{ movimiento.recurso || 'Recurso retirado' }}</strong><span>{{ etiquetaMovimiento(movimiento.tipo_movimiento) }} · {{ movimiento.descripcion }}</span></div><div><strong :class="Number(movimiento.cantidad) < 0 ? 'savix-pos-error' : ''">{{ Number(movimiento.cantidad) > 0 ? '+' : '' }}{{ formatoCantidad(movimiento.cantidad) }}</strong><span>{{ formatoCantidad(movimiento.existencia_anterior) }} → {{ formatoCantidad(movimiento.existencia_posterior) }}</span></div></article>
                    <p v-if="!movimientos.length" class="savix-pos-vacio">Todavía no hay movimientos registrados.</p>
                </div>
            </section>
        </section>
    </main>
</template>

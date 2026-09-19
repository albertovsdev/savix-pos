<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    sucursal_seleccionada: { type: Object, required: true }, sucursales: { type: Array, default: () => [] },
    mesas: { type: Array, default: () => [] }, productos: { type: Array, default: () => [] },
    pedido: { type: Object, default: null }, puede_configurar_mesas: { type: Boolean, default: false },
});

const mensaje = computed(() => usePage().props.flash?.exito);
const mesa = useForm({ nombre: '', orden: 0, ref_sucursal: props.sucursal_seleccionada.id_sucursal });
const mostrador = useForm({ ref_sucursal: props.sucursal_seleccionada.id_sucursal });
const detalle = useForm({ ref_producto: '', cantidad: 1, opciones_modificadores: [], nota_preparacion: '', ref_sucursal: props.sucursal_seleccionada.id_sucursal });
const productoSeleccionado = computed(() => props.productos.find((item) => String(item.id_producto) === String(detalle.ref_producto)));

watch(() => props.sucursal_seleccionada.id_sucursal, (idSucursal) => {
    mesa.ref_sucursal = idSucursal;
    mostrador.ref_sucursal = idSucursal;
    detalle.ref_sucursal = idSucursal;
});
watch(() => detalle.ref_producto, () => { detalle.opciones_modificadores = []; });

const cambiarSucursal = (evento) => router.get('/operacion', { ref_sucursal: evento.target.value }, { preserveState: false, preserveScroll: true });
const crearMesa = () => mesa.post('/operacion/mesas', { preserveScroll: true, onSuccess: () => mesa.reset('nombre', 'orden') });
const abrirMesa = (item) => router.post(`/operacion/mesas/${item.id_mesa}/abrir`, { ref_sucursal: props.sucursal_seleccionada.id_sucursal });
const abrirMostrador = () => mostrador.post('/operacion/mostrador');
const agregarProducto = () => detalle.post(`/operacion/pedidos/${props.pedido.id_pedido}/detalles`, { preserveScroll: true, onSuccess: () => detalle.reset('ref_producto', 'opciones_modificadores', 'nota_preparacion') });
const retirarDetalle = (item) => router.delete(`/operacion/detalles/${item.id_detalle_ronda_pedido}`, { data: { ref_sucursal: props.sucursal_seleccionada.id_sucursal }, preserveScroll: true });
const confirmarRonda = () => { if (window.confirm('¿Confirmas el envío de esta ronda? Ya no se podrá editar.')) router.post(`/operacion/pedidos/${props.pedido.id_pedido}/confirmar-ronda`, { ref_sucursal: props.sucursal_seleccionada.id_sucursal }, { preserveScroll: true }); };
</script>

<template>
    <Head title="Operación" />
    <main class="savix-pos-administracion">
        <header class="savix-pos-administracion__encabezado">
            <div class="savix-pos-marca"><span class="savix-pos-marca__sello">SI</span><div><p class="savix-pos-marca__nombre">Operación</p><p class="savix-pos-marca__subtitulo">Mesas, mostrador y rondas</p></div></div>
            <Link class="savix-pos-boton savix-pos-boton--secundario" href="/panel">Volver al panel</Link>
        </header>

        <section class="savix-pos-administracion__contenido">
            <p v-if="mensaje" class="savix-pos-notificacion" role="status">{{ mensaje }}</p>
            <div class="savix-pos-seccion__titulo"><p class="savix-pos-eyebrow">VENTA</p><h1>{{ pedido ? `${pedido.tipo_servicio === 'mesa' ? pedido.mesa : pedido.referencia_mostrador} · cuenta abierta` : 'Selecciona una mesa o abre mostrador' }}</h1><p v-if="pedido">Ronda {{ pedido.ronda_borrador?.numero_ronda }} en borrador. Las rondas confirmadas permanecen intactas.</p><p v-else>Trabajando en {{ sucursal_seleccionada.nombre }}.</p></div>
            <label class="savix-pos-campo savix-pos-campo--selector-sucursal"><span>Sucursal</span><select :value="sucursal_seleccionada.id_sucursal" @change="cambiarSucursal"><option v-for="item in sucursales" :key="item.id_sucursal" :value="item.id_sucursal">{{ item.nombre }}</option></select></label>

            <section v-if="!pedido" class="savix-pos-seccion">
                <div class="savix-pos-seccion__subtitulo"><h2>Mesas</h2><p>Una mesa ocupada recupera su misma cuenta abierta.</p></div>
                <div class="savix-pos-lista"><article v-for="item in mesas" :key="item.id_mesa" class="savix-pos-lista__fila"><div><strong>{{ item.nombre }}</strong><span>{{ item.estado === 'ocupada' ? 'Cuenta abierta' : 'Disponible' }}</span></div><span>{{ item.ref_pedido ? 'Continúa el pedido actual' : 'Abre una cuenta nueva' }}</span><button class="savix-pos-boton savix-pos-boton--primario" type="button" @click="abrirMesa(item)">{{ item.ref_pedido ? 'Abrir cuenta' : 'Abrir mesa' }}</button></article><p v-if="!mesas.length" class="savix-pos-vacio">Aún no hay mesas configuradas.</p></div>
                <form v-if="puede_configurar_mesas" class="savix-pos-formulario-interno" @submit.prevent="crearMesa"><div class="savix-pos-seccion__subtitulo"><h2>Nueva mesa</h2><p>Ejemplos: Mesa 1, Terraza 2 o Barra 1.</p></div><div class="savix-pos-cuadricula savix-pos-cuadricula--dos"><label class="savix-pos-campo"><span>Nombre</span><input v-model="mesa.nombre" required></label><label class="savix-pos-campo"><span>Orden</span><input v-model="mesa.orden" type="number" min="0" required></label></div><footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="mesa.processing">Crear mesa</button></footer></form>
                <div class="savix-pos-formulario-interno"><div class="savix-pos-seccion__subtitulo"><h2>Mostrador</h2><p>Abre un pedido sin usar mesa.</p></div><footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="mostrador.processing" @click="abrirMostrador">Abrir mostrador</button></footer></div>
            </section>

            <section v-else class="savix-pos-seccion">
                <Link class="savix-pos-enlace" :href="`/operacion?ref_sucursal=${sucursal_seleccionada.id_sucursal}`">Cambiar de mesa o mostrador</Link>
                <div class="savix-pos-seccion__subtitulo"><h2>Agregar a ronda {{ pedido.ronda_borrador?.numero_ronda }}</h2><p>Selecciona producto, extras y notas antes de confirmar.</p></div>
                <form class="savix-pos-formulario-interno" @submit.prevent="agregarProducto">
                    <div class="savix-pos-cuadricula savix-pos-cuadricula--dos"><label class="savix-pos-campo"><span>Producto</span><select v-model="detalle.ref_producto" required><option disabled value="">Selecciona un producto</option><option v-for="item in productos" :key="item.id_producto" :value="item.id_producto">{{ item.nombre }} · ${{ Number(item.precio_venta).toFixed(2) }}</option></select></label><label class="savix-pos-campo"><span>Cantidad</span><input v-model="detalle.cantidad" type="number" min="0.001" step="0.001" required></label><label class="savix-pos-campo savix-pos-campo--ancho"><span>Nota de preparación, opcional</span><textarea v-model="detalle.nota_preparacion" rows="2" placeholder="Ej. término medio"></textarea></label></div>
                    <fieldset v-for="grupo in productoSeleccionado?.grupos_modificadores" :key="grupo.id_grupo_modificador_producto" class="savix-pos-seleccion"><legend>{{ grupo.nombre }} · {{ grupo.minimo_selecciones }} a {{ grupo.maximo_selecciones }} selección(es)</legend><label v-for="opcion in grupo.opciones" :key="opcion.id_opcion_modificador_producto"><input v-model="detalle.opciones_modificadores" :value="opcion.id_opcion_modificador_producto" type="checkbox"> {{ opcion.nombre }} <span v-if="Number(opcion.precio_adicional) > 0">(+${{ Number(opcion.precio_adicional).toFixed(2) }})</span></label></fieldset>
                    <footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="detalle.processing">Agregar a ronda</button></footer>
                </form>

                <div class="savix-pos-seccion__subtitulo"><h2>Borrador actual</h2><p>Total de ronda: ${{ Number(pedido.ronda_borrador?.importe_total || 0).toFixed(2) }}</p></div>
                <div class="savix-pos-lista"><article v-for="item in pedido.detalles_borrador" :key="item.id_detalle_ronda_pedido" class="savix-pos-lista__fila"><div><strong>{{ item.cantidad }} × {{ item.nombre_producto }}</strong><span v-if="item.modificadores.length">{{ item.modificadores.map((modificador) => modificador.nombre_opcion).join(', ') }}</span><span v-if="item.nota_preparacion">Nota: {{ item.nota_preparacion }}</span></div><span>${{ Number(item.importe_total).toFixed(2) }}</span><button class="savix-pos-boton savix-pos-boton--secundario" type="button" @click="retirarDetalle(item)">Retirar</button></article><p v-if="!pedido.detalles_borrador.length" class="savix-pos-vacio">Agrega productos antes de confirmar esta ronda.</p></div>
                <footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="!pedido.detalles_borrador.length" @click="confirmarRonda">Confirmar y enviar ronda</button></footer>
                <div class="savix-pos-seccion__subtitulo"><h2>Rondas enviadas</h2><p>Total acumulado: ${{ Number(pedido.importe_total).toFixed(2) }}</p></div><div class="savix-pos-lista"><article v-for="ronda in pedido.rondas_enviadas" :key="ronda.id_ronda_pedido" class="savix-pos-lista__fila"><div><strong>Ronda {{ ronda.numero_ronda }}</strong><span>Confirmada y separada del borrador actual.</span></div><span>${{ Number(ronda.importe_total).toFixed(2) }}</span></article><p v-if="!pedido.rondas_enviadas.length" class="savix-pos-vacio">Aún no hay rondas enviadas.</p></div>
            </section>
        </section>
    </main>
</template>

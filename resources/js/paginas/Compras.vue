<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    sucursal_seleccionada: { type: Object, required: true }, sucursales: { type: Array, default: () => [] },
    proveedores: { type: Array, default: () => [] }, recursos: { type: Array, default: () => [] }, compras: { type: Array, default: () => [] },
    puede_registrar: { type: Boolean, default: false },
});

const mensaje = computed(() => usePage().props.flash?.exito);
const proveedor = useForm({ codigo: '', nombre: '', contacto: '', telefono: '', correo: '', direccion: '', ref_sucursal: props.sucursal_seleccionada.id_sucursal });
const compra = useForm({ ref_sucursal: props.sucursal_seleccionada.id_sucursal, ref_proveedor: '', fecha_compra: new Date().toISOString().slice(0, 10), folio_proveedor: '', notas: '', detalles: [{ recurso: '', cantidad: '1', costo_unitario: '' }] });
const cambiarSucursal = (evento) => router.get('/compras', { ref_sucursal: evento.target.value }, { preserveState: false, preserveScroll: true });
const claveRecurso = (recurso) => `${recurso.tipo_recurso}:${recurso.id_recurso}`;
const recursoPorClave = (clave) => props.recursos.find((recurso) => claveRecurso(recurso) === clave);
const actualizarCosto = (linea) => { const recurso = recursoPorClave(linea.recurso); if (recurso) linea.costo_unitario = Number(recurso.costo_sugerido).toFixed(4); };
const agregarDetalle = () => compra.detalles.push({ recurso: '', cantidad: '1', costo_unitario: '' });
const quitarDetalle = (indice) => { if (compra.detalles.length > 1) compra.detalles.splice(indice, 1); };
const totalCompra = computed(() => compra.detalles.reduce((total, detalle) => total + ((Number(detalle.cantidad) || 0) * (Number(detalle.costo_unitario) || 0)), 0));
const guardarProveedor = () => proveedor.post('/compras/proveedores', { preserveScroll: true, onSuccess: () => proveedor.reset('codigo', 'nombre', 'contacto', 'telefono', 'correo', 'direccion') });
const guardarCompra = () => compra.transform((datos) => ({ ...datos, detalles: datos.detalles.map((detalle) => { const [tipo_recurso, ref_recurso] = detalle.recurso.split(':'); return { tipo_recurso, ref_recurso, cantidad: detalle.cantidad, costo_unitario: detalle.costo_unitario }; }) })).post('/compras', { preserveScroll: true, onSuccess: () => { compra.reset('ref_proveedor', 'folio_proveedor', 'notas'); compra.fecha_compra = new Date().toISOString().slice(0, 10); compra.detalles = [{ recurso: '', cantidad: '1', costo_unitario: '' }]; } });
</script>

<template>
    <Head title="Compras" />
    <main class="savix-pos-administracion">
        <header class="savix-pos-administracion__encabezado"><div class="savix-pos-marca"><span class="savix-pos-marca__sello">SI</span><div><p class="savix-pos-marca__nombre">Compras</p><p class="savix-pos-marca__subtitulo">Proveedores y entradas</p></div></div><Link class="savix-pos-boton savix-pos-boton--secundario" href="/panel">Volver al panel</Link></header>
        <section class="savix-pos-administracion__contenido">
            <p v-if="mensaje" class="savix-pos-notificacion" role="status">{{ mensaje }}</p>
            <div class="savix-pos-seccion__titulo"><p class="savix-pos-eyebrow">ABASTECIMIENTO</p><h1>Compras de {{ sucursal_seleccionada.nombre }}</h1><p>Registra entradas reales; cada renglón incrementa la existencia correspondiente.</p></div>
            <label class="savix-pos-campo savix-pos-campo--selector-sucursal"><span>Sucursal</span><select :value="sucursal_seleccionada.id_sucursal" @change="cambiarSucursal"><option v-for="item in sucursales" :key="item.id_sucursal" :value="item.id_sucursal">{{ item.nombre }}</option></select></label>

            <form v-if="puede_registrar" class="savix-pos-formulario-interno" @submit.prevent="guardarProveedor"><div class="savix-pos-seccion__subtitulo"><h2>Nuevo proveedor</h2><p>El proveedor pertenece al negocio y podrá usarse en todas sus sucursales.</p></div><div class="savix-pos-cuadricula savix-pos-cuadricula--tres"><label class="savix-pos-campo"><span>Nombre</span><input v-model="proveedor.nombre" maxlength="160" required></label><label class="savix-pos-campo"><span>Contacto, opcional</span><input v-model="proveedor.contacto" maxlength="160"></label><label class="savix-pos-campo"><span>Teléfono, opcional</span><input v-model="proveedor.telefono" maxlength="30"></label><label class="savix-pos-campo"><span>Correo, opcional</span><input v-model="proveedor.correo" type="email"></label><label class="savix-pos-campo"><span>Código, opcional</span><input v-model="proveedor.codigo" maxlength="80"></label><label class="savix-pos-campo"><span>Dirección, opcional</span><input v-model="proveedor.direccion"></label></div><footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="proveedor.processing">{{ proveedor.processing ? 'Guardando…' : 'Crear proveedor' }}</button></footer></form>

            <form v-if="puede_registrar" class="savix-pos-formulario-interno" @submit.prevent="guardarCompra"><div class="savix-pos-seccion__subtitulo"><h2>Nueva entrada por compra</h2><p>Los costos son importes finales. El IVA se desglosa conforme a la configuración del negocio.</p></div><div class="savix-pos-cuadricula savix-pos-cuadricula--tres"><label class="savix-pos-campo"><span>Proveedor, opcional</span><select v-model="compra.ref_proveedor"><option value="">Compra sin proveedor</option><option v-for="item in proveedores" :key="item.id_proveedor" :value="item.id_proveedor">{{ item.nombre }}</option></select></label><label class="savix-pos-campo"><span>Fecha</span><input v-model="compra.fecha_compra" type="date" required></label><label class="savix-pos-campo"><span>Folio del proveedor, opcional</span><input v-model="compra.folio_proveedor" maxlength="120"></label></div><div v-for="(detalle, indice) in compra.detalles" :key="indice" class="savix-pos-cuadricula savix-pos-cuadricula--tres"><label class="savix-pos-campo"><span>Producto o insumo</span><select v-model="detalle.recurso" required @change="actualizarCosto(detalle)"><option disabled value="">Selecciona un recurso</option><option v-for="recurso in recursos" :key="claveRecurso(recurso)" :value="claveRecurso(recurso)">{{ recurso.tipo_recurso === 'producto' ? 'Producto' : 'Insumo' }} · {{ recurso.nombre }}</option></select></label><label class="savix-pos-campo"><span>Cantidad</span><input v-model="detalle.cantidad" type="number" min="0.0001" step="0.0001" required></label><label class="savix-pos-campo"><span>Costo unitario final</span><input v-model="detalle.costo_unitario" type="number" min="0" step="0.0001" required></label><button v-if="compra.detalles.length > 1" class="savix-pos-enlace" type="button" @click="quitarDetalle(indice)">Quitar renglón</button></div><label class="savix-pos-campo"><span>Notas, opcional</span><textarea v-model="compra.notas" rows="2"></textarea></label><p>Total estimado: ${{ totalCompra.toFixed(2) }}</p><p v-if="compra.errors.detalles" class="savix-pos-error">{{ compra.errors.detalles }}</p><footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--secundario" type="button" @click="agregarDetalle">Agregar renglón</button><button class="savix-pos-boton savix-pos-boton--primario" :disabled="compra.processing">{{ compra.processing ? 'Registrando…' : 'Registrar compra' }}</button></footer></form>

            <section class="savix-pos-seccion"><div class="savix-pos-seccion__subtitulo"><h2>Últimas compras</h2><p>Las últimas 30 entradas registradas en esta sucursal.</p></div><div class="savix-pos-lista"><article v-for="item in compras" :key="item.id_compra" class="savix-pos-lista__fila"><div><strong>{{ item.proveedor || 'Sin proveedor' }}</strong><span>{{ item.fecha_compra }}<span v-if="item.folio_proveedor"> · {{ item.folio_proveedor }}</span></span></div><strong>${{ Number(item.importe_total).toFixed(2) }}</strong></article><p v-if="!compras.length" class="savix-pos-vacio">Todavía no hay compras registradas.</p></div></section>
        </section>
    </main>
</template>

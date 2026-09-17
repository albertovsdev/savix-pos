<script setup>
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    negocio: { type: Object, required: true },
    sucursales: { type: Array, default: () => [] },
    sucursal_seleccionada: { type: Object, required: true },
    categorias: { type: Array, default: () => [] },
    productos: { type: Array, default: () => [] },
    areas_preparacion: { type: Array, default: () => [] },
    tipos_inventario: { type: Array, default: () => [] },
    puede_gestionar: { type: Boolean, default: false },
});

const seccion = ref('productos');
const mensaje = computed(() => usePage().props.flash?.exito);
const categoria = useForm({ nombre: '', descripcion: '', orden: 0 });
const area = useForm({ nombre: '', codigo: '', orden: 0, ref_sucursal: props.sucursal_seleccionada.id_sucursal });
const producto = useForm({
    ref_categoria_producto: '', codigo: '', nombre: '', descripcion: '', precio_compra: '0.00', precio_venta: '',
    tipo_inventario: 'sin_control', areas_preparacion: [], ref_sucursal: props.sucursal_seleccionada.id_sucursal,
});

watch(() => props.sucursal_seleccionada.id_sucursal, (idSucursal) => {
    area.ref_sucursal = idSucursal;
    producto.ref_sucursal = idSucursal;
    producto.areas_preparacion = [];
});

const cambiarSucursal = (evento) => router.get('/catalogos', { ref_sucursal: evento.target.value }, { preserveState: false, preserveScroll: true });
const guardarCategoria = () => categoria.post('/catalogos/categorias', { preserveScroll: true, onSuccess: () => categoria.reset() });
const guardarArea = () => area.post('/catalogos/areas-preparacion', { preserveScroll: true, onSuccess: () => area.reset('nombre', 'codigo', 'orden') });
const guardarProducto = () => producto.transform((datos) => ({ ...datos, ref_categoria_producto: datos.ref_categoria_producto || null, codigo: datos.codigo || null })).post('/catalogos/productos', { preserveScroll: true, onSuccess: () => producto.reset('ref_categoria_producto', 'codigo', 'nombre', 'descripcion', 'precio_compra', 'precio_venta', 'tipo_inventario', 'areas_preparacion') });
const alternarCategoria = (item) => router.put(`/catalogos/categorias/${item.id_categoria_producto}/disponibilidad`, { ref_sucursal: props.sucursal_seleccionada.id_sucursal, habilitada: !Boolean(item.habilitada) }, { preserveScroll: true });
const alternarProducto = (item) => router.put(`/catalogos/productos/${item.id_producto}/disponibilidad`, { ref_sucursal: props.sucursal_seleccionada.id_sucursal, habilitado: !Boolean(item.habilitado) }, { preserveScroll: true });
</script>

<template>
    <Head title="Catálogos" />

    <main class="savix-pos-administracion">
        <header class="savix-pos-administracion__encabezado">
            <div class="savix-pos-marca">
                <span class="savix-pos-marca__sello">SI</span>
                <div><p class="savix-pos-marca__nombre">{{ negocio.nombre_comercial }}</p><p class="savix-pos-marca__subtitulo">Catálogo de la operación</p></div>
            </div>
            <Link class="savix-pos-boton savix-pos-boton--secundario" href="/panel">Volver al panel</Link>
        </header>

        <div class="savix-pos-administracion__marco">
            <nav class="savix-pos-administracion__navegacion" aria-label="Secciones de catálogo">
                <button v-for="item in [['productos', 'Productos'], ['categorias', 'Categorías'], ['areas', 'Áreas de preparación']]" :key="item[0]" class="savix-pos-navegacion__item" :class="{ 'savix-pos-navegacion__item--activo': seccion === item[0] }" @click="seccion = item[0]">{{ item[1] }}</button>
            </nav>

            <section class="savix-pos-administracion__contenido">
                <p v-if="mensaje" class="savix-pos-notificacion" role="status">{{ mensaje }}</p>
                <div class="savix-pos-seccion__titulo">
                    <p class="savix-pos-eyebrow">CATÁLOGO GLOBAL</p>
                    <h1>Productos que se adaptan a cada sucursal</h1>
                    <p>El producto se registra una vez; aquí decides si está disponible y a dónde debe enviarse en {{ sucursal_seleccionada.nombre }}.</p>
                </div>
                <label class="savix-pos-campo savix-pos-campo--selector-sucursal"><span>Sucursal que estás configurando</span><select :value="sucursal_seleccionada.id_sucursal" @change="cambiarSucursal"><option v-for="item in sucursales" :key="item.id_sucursal" :value="item.id_sucursal">{{ item.nombre }}</option></select></label>

                <section v-if="seccion === 'productos'" class="savix-pos-seccion">
                    <div class="savix-pos-seccion__subtitulo"><h2>Productos disponibles</h2><p>Desactiva los que esta sucursal no vende, sin eliminarlos del catálogo global.</p></div>
                    <div class="savix-pos-lista">
                        <article v-for="item in productos" :key="item.id_producto" class="savix-pos-lista__fila">
                            <div><strong>{{ item.nombre }}</strong><span>{{ item.categoria || 'Sin categoría' }} · {{ item.tipo_inventario_nombre }} · ${{ Number(item.precio_venta).toFixed(2) }}</span><span v-if="item.areas_preparacion.length">{{ item.areas_preparacion.join(', ') }}</span></div>
                            <label v-if="puede_gestionar" class="savix-pos-opcion"><input :checked="Boolean(item.habilitado)" type="checkbox" @change="alternarProducto(item)"> Disponible</label>
                            <span v-else>{{ item.habilitado ? 'Disponible' : 'No disponible' }}</span>
                        </article>
                        <p v-if="!productos.length" class="savix-pos-vacio">Aún no hay productos. Registra el primero para empezar a vender.</p>
                    </div>
                    <form v-if="puede_gestionar" class="savix-pos-formulario-interno" @submit.prevent="guardarProducto">
                        <div class="savix-pos-seccion__subtitulo"><h2>Nuevo producto</h2><p>En la siguiente entrega se configurarán recetas, ingredientes, extras y modificadores.</p></div>
                        <div class="savix-pos-cuadricula savix-pos-cuadricula--dos">
                            <label class="savix-pos-campo"><span>Nombre</span><input v-model="producto.nombre" required></label>
                            <label class="savix-pos-campo"><span>Categoría</span><select v-model="producto.ref_categoria_producto"><option value="">Sin categoría</option><option v-for="item in categorias" :key="item.id_categoria_producto" :value="item.id_categoria_producto">{{ item.nombre }}</option></select></label>
                            <label class="savix-pos-campo"><span>Código interno, opcional</span><input v-model="producto.codigo" maxlength="60"></label>
                            <label class="savix-pos-campo"><span>Tipo de inventario</span><select v-model="producto.tipo_inventario"><option v-for="item in tipos_inventario" :key="item.valor" :value="item.valor">{{ item.nombre }}</option></select></label>
                            <label class="savix-pos-campo"><span>Precio de compra final (MXN)</span><input v-model="producto.precio_compra" type="number" min="0" step="0.01" required></label>
                            <label class="savix-pos-campo"><span>Precio de venta final (MXN)</span><input v-model="producto.precio_venta" type="number" min="0" step="0.01" required></label>
                            <label class="savix-pos-campo savix-pos-campo--ancho"><span>Descripción, opcional</span><textarea v-model="producto.descripcion" rows="2"></textarea></label>
                        </div>
                        <fieldset class="savix-pos-seleccion"><legend>Enviar a preparación en esta sucursal</legend><label v-for="item in areas_preparacion" :key="item.id_area_preparacion"><input v-model="producto.areas_preparacion" :value="item.id_area_preparacion" type="checkbox"> {{ item.nombre }}</label><p v-if="!areas_preparacion.length" class="savix-pos-vacio">Primero crea las áreas necesarias, por ejemplo Cocina o Barra.</p></fieldset>
                        <footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="producto.processing">{{ producto.processing ? 'Guardando…' : 'Crear producto' }}</button></footer>
                    </form>
                </section>

                <section v-if="seccion === 'categorias'" class="savix-pos-seccion">
                    <div class="savix-pos-seccion__subtitulo"><h2>Categorías</h2><p>Sirven para ordenar la venta y también se pueden ocultar por sucursal.</p></div>
                    <div class="savix-pos-lista"><article v-for="item in categorias" :key="item.id_categoria_producto" class="savix-pos-lista__fila"><div><strong>{{ item.nombre }}</strong><span>{{ item.descripcion || 'Sin descripción' }}</span></div><label v-if="puede_gestionar" class="savix-pos-opcion"><input :checked="Boolean(item.habilitada)" type="checkbox" @change="alternarCategoria(item)"> Visible</label><span v-else>{{ item.habilitada ? 'Visible' : 'Oculta' }}</span></article><p v-if="!categorias.length" class="savix-pos-vacio">Aún no hay categorías.</p></div>
                    <form v-if="puede_gestionar" class="savix-pos-formulario-interno" @submit.prevent="guardarCategoria"><div class="savix-pos-seccion__subtitulo"><h2>Nueva categoría</h2><p>Quedará visible de inicio en todas las sucursales.</p></div><div class="savix-pos-cuadricula savix-pos-cuadricula--dos"><label class="savix-pos-campo"><span>Nombre</span><input v-model="categoria.nombre" required></label><label class="savix-pos-campo"><span>Orden</span><input v-model="categoria.orden" type="number" min="0" required></label><label class="savix-pos-campo savix-pos-campo--ancho"><span>Descripción, opcional</span><textarea v-model="categoria.descripcion" rows="2"></textarea></label></div><footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="categoria.processing">{{ categoria.processing ? 'Guardando…' : 'Crear categoría' }}</button></footer></form>
                </section>

                <section v-if="seccion === 'areas'" class="savix-pos-seccion">
                    <div class="savix-pos-seccion__subtitulo"><h2>Áreas de preparación</h2><p>Solo aparecen las que este negocio decidió usar en {{ sucursal_seleccionada.nombre }}.</p></div>
                    <div class="savix-pos-lista"><article v-for="item in areas_preparacion" :key="item.id_area_preparacion" class="savix-pos-lista__fila"><div><strong>{{ item.nombre }}</strong><span>Código {{ item.codigo }} · {{ item.activo ? 'Activa' : 'Inactiva' }}</span></div></article><p v-if="!areas_preparacion.length" class="savix-pos-vacio">No hay áreas configuradas todavía.</p></div>
                    <form v-if="puede_gestionar" class="savix-pos-formulario-interno" @submit.prevent="guardarArea"><div class="savix-pos-seccion__subtitulo"><h2>Nueva área</h2><p>Ejemplos: Cocina, Barra o Postres.</p></div><div class="savix-pos-cuadricula savix-pos-cuadricula--tres"><label class="savix-pos-campo"><span>Nombre</span><input v-model="area.nombre" required></label><label class="savix-pos-campo"><span>Código, opcional</span><input v-model="area.codigo" maxlength="40"><small>Se genera si lo dejas vacío.</small></label><label class="savix-pos-campo"><span>Orden</span><input v-model="area.orden" type="number" min="0" required></label></div><footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="area.processing">{{ area.processing ? 'Guardando…' : 'Crear área' }}</button></footer></form>
                </section>
            </section>
        </div>
    </main>
</template>

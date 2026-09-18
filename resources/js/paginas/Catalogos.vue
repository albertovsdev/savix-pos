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
    unidades_medida: { type: Array, default: () => [] },
    insumos: { type: Array, default: () => [] },
    recetas: { type: Array, default: () => [] },
    grupos_modificadores: { type: Array, default: () => [] },
    opciones_modificadores: { type: Array, default: () => [] },
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
const insumo = useForm({
    codigo: '', nombre: '', ref_unidad_medida: props.unidades_medida[0]?.id_unidad_medida || '', costo_unitario: '0.0000',
    existencia_actual: '0.0000', existencia_minima: '0.0000', ref_sucursal: props.sucursal_seleccionada.id_sucursal,
});
const receta = useForm({
    ref_producto: '', ref_insumo: '', cantidad: '', ref_sucursal: props.sucursal_seleccionada.id_sucursal,
});
const recetasDelProducto = computed(() => props.recetas.filter((item) => String(item.ref_producto) === String(receta.ref_producto)));
const grupoModificador = useForm({ ref_producto: '', nombre: '', minimo_selecciones: 0, maximo_selecciones: 1, ref_sucursal: props.sucursal_seleccionada.id_sucursal });
const opcionModificador = useForm({ ref_grupo_modificador_producto: '', nombre: '', tipo_modificacion: 'nota', ref_insumo: '', cantidad_insumo: '0.0000', precio_adicional: '0.00', ref_sucursal: props.sucursal_seleccionada.id_sucursal });
const opcionesDelGrupo = computed(() => props.opciones_modificadores.filter((item) => String(item.ref_grupo_modificador_producto) === String(opcionModificador.ref_grupo_modificador_producto)));

watch(() => props.sucursal_seleccionada.id_sucursal, (idSucursal) => {
    area.ref_sucursal = idSucursal;
    producto.ref_sucursal = idSucursal;
    insumo.ref_sucursal = idSucursal;
    receta.ref_sucursal = idSucursal;
    grupoModificador.ref_sucursal = idSucursal;
    opcionModificador.ref_sucursal = idSucursal;
    producto.areas_preparacion = [];
});

const cambiarSucursal = (evento) => router.get('/catalogos', { ref_sucursal: evento.target.value }, { preserveState: false, preserveScroll: true });
const guardarCategoria = () => categoria.post('/catalogos/categorias', { preserveScroll: true, onSuccess: () => categoria.reset() });
const guardarArea = () => area.post('/catalogos/areas-preparacion', { preserveScroll: true, onSuccess: () => area.reset('nombre', 'codigo', 'orden') });
const guardarProducto = () => producto.transform((datos) => ({ ...datos, ref_categoria_producto: datos.ref_categoria_producto || null, codigo: datos.codigo || null })).post('/catalogos/productos', { preserveScroll: true, onSuccess: () => producto.reset('ref_categoria_producto', 'codigo', 'nombre', 'descripcion', 'precio_compra', 'precio_venta', 'tipo_inventario', 'areas_preparacion') });
const guardarInsumo = () => insumo.transform((datos) => ({ ...datos, codigo: datos.codigo || null })).post('/catalogos/insumos', { preserveScroll: true, onSuccess: () => insumo.reset('codigo', 'nombre', 'costo_unitario', 'existencia_actual', 'existencia_minima') });
const guardarReceta = () => receta.post('/catalogos/recetas', { preserveScroll: true, onSuccess: () => receta.reset('ref_insumo', 'cantidad') });
const guardarGrupoModificador = () => grupoModificador.post('/catalogos/grupos-modificadores', { preserveScroll: true, onSuccess: () => grupoModificador.reset('ref_producto', 'nombre', 'minimo_selecciones', 'maximo_selecciones') });
const guardarOpcionModificador = () => opcionModificador.transform((datos) => ({ ...datos, ref_insumo: datos.ref_insumo || null, cantidad_insumo: datos.cantidad_insumo || 0 })).post('/catalogos/opciones-modificadores', { preserveScroll: true, onSuccess: () => opcionModificador.reset('nombre', 'ref_insumo', 'cantidad_insumo', 'precio_adicional') });
const retirarOpcionModificador = (item) => router.delete(`/catalogos/opciones-modificadores/${item.id_opcion_modificador_producto}`, { data: { ref_sucursal: props.sucursal_seleccionada.id_sucursal }, preserveScroll: true });
const retirarInsumoReceta = (item) => router.delete(`/catalogos/recetas/${item.id_receta_producto}`, { data: { ref_sucursal: props.sucursal_seleccionada.id_sucursal }, preserveScroll: true });
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
                <button v-for="item in [['productos', 'Productos'], ['categorias', 'Categorías'], ['areas', 'Áreas de preparación'], ['insumos', 'Insumos'], ['recetas', 'Recetas'], ['modificadores', 'Opciones y extras']]" :key="item[0]" class="savix-pos-navegacion__item" :class="{ 'savix-pos-navegacion__item--activo': seccion === item[0] }" @click="seccion = item[0]">{{ item[1] }}</button>
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
                <section v-if="seccion === 'recetas'" class="savix-pos-seccion">
                    <div class="savix-pos-seccion__subtitulo"><h2>Recetas de productos</h2><p>Define la cantidad de cada insumo usada por una unidad vendida.</p></div>
                    <form v-if="puede_gestionar" class="savix-pos-formulario-interno" @submit.prevent="guardarReceta">
                        <div class="savix-pos-seccion__subtitulo"><h2>Agregar o actualizar insumo</h2><p>Si el insumo ya existe en la receta, su cantidad se reemplaza.</p></div>
                        <div class="savix-pos-cuadricula savix-pos-cuadricula--tres">
                            <label class="savix-pos-campo"><span>Producto</span><select v-model="receta.ref_producto" required><option disabled value="">Selecciona un producto</option><option v-for="item in productos" :key="item.id_producto" :value="item.id_producto">{{ item.nombre }}</option></select></label>
                            <label class="savix-pos-campo"><span>Insumo</span><select v-model="receta.ref_insumo" required><option disabled value="">Selecciona un insumo</option><option v-for="item in insumos" :key="item.id_insumo" :value="item.id_insumo">{{ item.nombre }} ({{ item.abreviatura }})</option></select></label>
                            <label class="savix-pos-campo"><span>Cantidad por producto</span><input v-model="receta.cantidad" type="number" min="0.0001" step="0.0001" required></label>
                        </div>
                        <footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="receta.processing">{{ receta.processing ? 'Guardando…' : 'Guardar en receta' }}</button></footer>
                    </form>
                    <div v-if="receta.ref_producto" class="savix-pos-lista">
                        <article v-for="item in recetasDelProducto" :key="item.id_receta_producto" class="savix-pos-lista__fila"><div><strong>{{ item.insumo }}</strong><span>{{ item.cantidad }} {{ item.abreviatura }} por {{ item.producto }}</span></div><span>Se descontará al enviar a preparación.</span><button v-if="puede_gestionar" class="savix-pos-boton savix-pos-boton--secundario" type="button" @click="retirarInsumoReceta(item)">Retirar</button></article>
                        <p v-if="!recetasDelProducto.length" class="savix-pos-vacio">Este producto todavía no tiene insumos en su receta.</p>
                    </div>
                    <p v-else class="savix-pos-vacio">Selecciona un producto para consultar su receta.</p>
                </section>

                <section v-if="seccion === 'modificadores'" class="savix-pos-seccion">
                    <div class="savix-pos-seccion__subtitulo"><h2>Opciones y extras</h2><p>Configura qué cambia la preparación, el consumo o el precio del producto.</p></div>
                    <form v-if="puede_gestionar" class="savix-pos-formulario-interno" @submit.prevent="guardarGrupoModificador">
                        <div class="savix-pos-seccion__subtitulo"><h2>Nuevo grupo de opciones</h2><p>Ejemplos: Preparación, Extras o Salsas.</p></div>
                        <div class="savix-pos-cuadricula savix-pos-cuadricula--tres">
                            <label class="savix-pos-campo"><span>Producto</span><select v-model="grupoModificador.ref_producto" required><option disabled value="">Selecciona un producto</option><option v-for="item in productos" :key="item.id_producto" :value="item.id_producto">{{ item.nombre }}</option></select></label>
                            <label class="savix-pos-campo"><span>Nombre del grupo</span><input v-model="grupoModificador.nombre" placeholder="Ej. Extras" required></label>
                            <label class="savix-pos-campo"><span>Mínimo de selecciones</span><input v-model="grupoModificador.minimo_selecciones" type="number" min="0" max="50" required></label>
                            <label class="savix-pos-campo"><span>Máximo de selecciones</span><input v-model="grupoModificador.maximo_selecciones" type="number" min="1" max="50" required></label>
                        </div>
                        <footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="grupoModificador.processing">{{ grupoModificador.processing ? 'Guardando…' : 'Crear grupo' }}</button></footer>
                    </form>

                    <form v-if="puede_gestionar" class="savix-pos-formulario-interno" @submit.prevent="guardarOpcionModificador">
                        <div class="savix-pos-seccion__subtitulo"><h2>Nueva opción o extra</h2><p>Un extra agrega insumo y precio; una eliminación evita consumir ese insumo.</p></div>
                        <div class="savix-pos-cuadricula savix-pos-cuadricula--dos">
                            <label class="savix-pos-campo"><span>Grupo</span><select v-model="opcionModificador.ref_grupo_modificador_producto" required><option disabled value="">Selecciona un grupo</option><option v-for="item in grupos_modificadores" :key="item.id_grupo_modificador_producto" :value="item.id_grupo_modificador_producto">{{ item.producto }} · {{ item.nombre }}</option></select></label>
                            <label class="savix-pos-campo"><span>Nombre de la opción</span><input v-model="opcionModificador.nombre" placeholder="Ej. Sin aguacate" required></label>
                            <label class="savix-pos-campo"><span>Efecto</span><select v-model="opcionModificador.tipo_modificacion"><option value="eliminar_insumo">Eliminar insumo de receta</option><option value="agregar_insumo">Agregar insumo / extra</option><option value="nota">Solo nota de preparación</option></select></label>
                            <label v-if="opcionModificador.tipo_modificacion !== 'nota'" class="savix-pos-campo"><span>Insumo afectado</span><select v-model="opcionModificador.ref_insumo" required><option disabled value="">Selecciona un insumo</option><option v-for="item in insumos" :key="item.id_insumo" :value="item.id_insumo">{{ item.nombre }} ({{ item.abreviatura }})</option></select></label>
                            <label v-if="opcionModificador.tipo_modificacion === 'agregar_insumo'" class="savix-pos-campo"><span>Cantidad extra</span><input v-model="opcionModificador.cantidad_insumo" type="number" min="0.0001" step="0.0001" required></label>
                            <label class="savix-pos-campo"><span>Precio adicional (MXN)</span><input v-model="opcionModificador.precio_adicional" type="number" min="0" step="0.01" required></label>
                        </div>
                        <footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="opcionModificador.processing">{{ opcionModificador.processing ? 'Guardando…' : 'Agregar opción' }}</button></footer>
                    </form>

                    <div v-if="opcionModificador.ref_grupo_modificador_producto" class="savix-pos-lista">
                        <article v-for="item in opcionesDelGrupo" :key="item.id_opcion_modificador_producto" class="savix-pos-lista__fila"><div><strong>{{ item.nombre }}</strong><span>{{ item.tipo_modificacion === 'eliminar_insumo' ? 'No consume' : item.tipo_modificacion === 'agregar_insumo' ? 'Agrega' : 'Nota de preparación' }}{{ item.insumo ? ': ' + item.insumo : '' }}</span></div><span v-if="item.tipo_modificacion === 'agregar_insumo'">{{ item.cantidad_insumo }} {{ item.abreviatura }} · +${{ Number(item.precio_adicional).toFixed(2) }}</span><span v-else>{{ Number(item.precio_adicional).toFixed(2) > 0 ? '+' : '' }}${{ Number(item.precio_adicional).toFixed(2) }}</span><button v-if="puede_gestionar" class="savix-pos-boton savix-pos-boton--secundario" type="button" @click="retirarOpcionModificador(item)">Retirar</button></article>
                        <p v-if="!opcionesDelGrupo.length" class="savix-pos-vacio">Este grupo todavía no tiene opciones.</p>
                    </div>
                    <p v-else class="savix-pos-vacio">Selecciona un grupo para revisar sus opciones.</p>
                </section>

                <section v-if="seccion === 'insumos'" class="savix-pos-seccion">
                    <div class="savix-pos-seccion__subtitulo"><h2>Insumos de {{ sucursal_seleccionada.nombre }}</h2><p>La existencia pertenece a esta sucursal.</p></div>
                    <div class="savix-pos-lista">
                        <article v-for="item in insumos" :key="item.id_insumo" class="savix-pos-lista__fila"><div><strong>{{ item.nombre }}</strong><span>{{ item.codigo || 'Sin código' }} · {{ item.unidad_medida }} ({{ item.abreviatura }})</span></div><div><span>Existencia: {{ Number(item.existencia_actual).toFixed(4) }} {{ item.abreviatura }}</span><span>Mínimo: {{ Number(item.existencia_minima).toFixed(4) }} {{ item.abreviatura }}</span></div><span>Costo unitario: ${{ Number(item.costo_unitario).toFixed(4) }}</span></article>
                        <p v-if="!insumos.length" class="savix-pos-vacio">Aún no hay insumos. Registra los ingredientes que usarán tus recetas.</p>
                    </div>
                    <form v-if="puede_gestionar" class="savix-pos-formulario-interno" @submit.prevent="guardarInsumo">
                        <div class="savix-pos-seccion__subtitulo"><h2>Nuevo insumo</h2><p>Indica existencias iniciales para {{ sucursal_seleccionada.nombre }}.</p></div>
                        <div class="savix-pos-cuadricula savix-pos-cuadricula--dos">
                            <label class="savix-pos-campo"><span>Nombre</span><input v-model="insumo.nombre" required></label><label class="savix-pos-campo"><span>Código interno, opcional</span><input v-model="insumo.codigo" maxlength="80"></label><label class="savix-pos-campo"><span>Unidad de medida</span><select v-model="insumo.ref_unidad_medida" required><option v-for="item in unidades_medida" :key="item.id_unidad_medida" :value="item.id_unidad_medida">{{ item.nombre }} ({{ item.abreviatura }})</option></select></label><label class="savix-pos-campo"><span>Costo unitario (MXN)</span><input v-model="insumo.costo_unitario" type="number" min="0" step="0.0001" required></label><label class="savix-pos-campo"><span>Existencia inicial</span><input v-model="insumo.existencia_actual" type="number" min="0" step="0.0001" required></label><label class="savix-pos-campo"><span>Existencia mínima</span><input v-model="insumo.existencia_minima" type="number" min="0" step="0.0001" required></label>
                        </div>
                        <footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="insumo.processing">{{ insumo.processing ? 'Guardando…' : 'Crear insumo' }}</button></footer>
                    </form>
                </section>
            </section>
        </div>
    </main>
</template>

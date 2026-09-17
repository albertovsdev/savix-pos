<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    negocio: { type: Object, required: true }, sucursales: { type: Array, default: () => [] },
    usuarios: { type: Array, default: () => [] }, roles: { type: Array, default: () => [] },
    permisos: { type: Array, default: () => [] }, modulos: { type: Array, default: () => [] },
    puede: { type: Object, required: true },
});

const seccion = ref('negocio');
const editandoSucursal = ref(null);
const mensaje = computed(() => usePage().props.flash?.exito);
const negocio = useForm({ ...props.negocio, logo: null, precios_incluyen_iva: Boolean(props.negocio.precios_incluyen_iva) });
const sucursal = useForm({ clave: '', nombre: '', telefono: '', correo: '', direccion: '', activo: true, modulos: props.modulos.map(({ id_modulo }) => id_modulo) });
const usuario = useForm({ nombre: '', nombre_usuario: '', correo: '', contrasena: '', contrasena_confirmation: '', pin: '', pin_confirmation: '', ref_sucursal: props.sucursales[0]?.id_sucursal || '', ref_rol: props.roles.find((rol) => rol.codigo === 'mesero')?.id_rol || '' });
const permiso = useForm({ ref_usuario: '', ref_permiso: props.permisos[0]?.id_permiso || '', tipo_asignacion: 'permitir', ref_sucursal: '', motivo: '' });

const guardarNegocio = () => negocio.put('/administracion/negocio', { preserveScroll: true });
const nuevaSucursal = () => { editandoSucursal.value = null; sucursal.reset(); sucursal.activo = true; sucursal.modulos = props.modulos.map(({ id_modulo }) => id_modulo); };
const editarSucursal = (item) => { editandoSucursal.value = item.id_sucursal; Object.assign(sucursal, { ...item, telefono: item.telefono || '', correo: item.correo || '', direccion: item.direccion || '', modulos: [...item.modulos] }); };
const guardarSucursal = () => editandoSucursal.value ? sucursal.put(`/administracion/sucursales/${editandoSucursal.value}`, { preserveScroll: true, onSuccess: nuevaSucursal }) : sucursal.post('/administracion/sucursales', { preserveScroll: true, onSuccess: nuevaSucursal });
const guardarUsuario = () => usuario.post('/administracion/usuarios', { preserveScroll: true, onSuccess: () => usuario.reset('nombre', 'nombre_usuario', 'correo', 'contrasena', 'contrasena_confirmation', 'pin', 'pin_confirmation') });
const guardarPermiso = () => permiso.transform((datos) => ({ ...datos, ref_sucursal: datos.ref_sucursal || null })).put(`/administracion/usuarios/${permiso.ref_usuario}/permisos`, { preserveScroll: true, onSuccess: () => permiso.reset('ref_usuario', 'ref_sucursal', 'motivo') });
</script>

<template>
    <Head title="Administración" />
    <main class="savix-pos-administracion">
        <header class="savix-pos-administracion__encabezado">
            <div class="savix-pos-marca"><img v-if="props.negocio.logo_url" :src="props.negocio.logo_url" class="savix-pos-marca__logo" alt="Logo del negocio"><span v-else class="savix-pos-marca__sello">SI</span><div><p class="savix-pos-marca__nombre">{{ negocio.nombre_comercial }}</p><p class="savix-pos-marca__subtitulo">Administración de la operación</p></div></div>
            <Link class="savix-pos-boton savix-pos-boton--secundario" href="/panel">Volver al panel</Link>
        </header>
        <div class="savix-pos-administracion__marco">
            <nav class="savix-pos-administracion__navegacion" aria-label="Secciones de administración">
                <button v-for="item in [['negocio', 'Negocio y ticket'], ['sucursales', 'Sucursales'], ['equipo', 'Equipo'], ['permisos', 'Permisos']]" :key="item[0]" class="savix-pos-navegacion__item" :class="{ 'savix-pos-navegacion__item--activo': seccion === item[0] }" @click="seccion = item[0]">{{ item[1] }}</button>
            </nav>
            <section class="savix-pos-administracion__contenido">
                <p v-if="mensaje" class="savix-pos-notificacion" role="status">{{ mensaje }}</p>

                <form v-if="seccion === 'negocio'" class="savix-pos-seccion" @submit.prevent="guardarNegocio">
                    <div class="savix-pos-seccion__titulo"><p class="savix-pos-eyebrow">NEGOCIO</p><h1>Identidad, precios y ticket</h1><p>Define cómo opera y se presenta esta instalación.</p></div>
                    <div class="savix-pos-cuadricula savix-pos-cuadricula--dos">
                        <label class="savix-pos-campo"><span>Razón o nombre</span><input v-model="negocio.nombre" :disabled="!puede.configurar_negocio" required></label>
                        <label class="savix-pos-campo"><span>Nombre comercial</span><input v-model="negocio.nombre_comercial" :disabled="!puede.configurar_negocio" required></label>
                        <label class="savix-pos-campo"><span>Clave de folio</span><input v-model="negocio.clave_folio" :disabled="!puede.configurar_negocio" maxlength="12" required><small>Ejemplo: SX-SM-0001</small></label>
                        <label class="savix-pos-campo"><span>RFC, opcional</span><input v-model="negocio.rfc" :disabled="!puede.configurar_negocio"></label>
                        <label class="savix-pos-campo"><span>Correo</span><input v-model="negocio.correo" :disabled="!puede.configurar_negocio" type="email"></label>
                        <label class="savix-pos-campo"><span>Teléfono</span><input v-model="negocio.telefono" :disabled="!puede.configurar_negocio"></label>
                    </div>
                    <div class="savix-pos-seccion__subtitulo"><h2>Apariencia</h2><p>Se aplica al guardar.</p></div>
                    <label class="savix-pos-campo"><span>Logo del negocio, opcional</span><input :disabled="!puede.configurar_negocio" accept="image/png,image/jpeg,image/webp" type="file" @change="(evento) => negocio.logo = evento.target.files[0]"><small>PNG, JPG o WebP, máximo 2 MB.</small></label>
                    <div class="savix-pos-cuadricula savix-pos-cuadricula--tres">
                        <label class="savix-pos-campo"><span>Color primario</span><input v-model="negocio.color_primario" :disabled="!puede.configurar_negocio" type="color"><small>{{ negocio.color_primario }}</small></label>
                        <label class="savix-pos-campo"><span>Color secundario</span><input v-model="negocio.color_secundario" :disabled="!puede.configurar_negocio" type="color"><small>{{ negocio.color_secundario }}</small></label>
                        <label class="savix-pos-campo"><span>Color de acento</span><input v-model="negocio.color_acento" :disabled="!puede.configurar_negocio" type="color"><small>{{ negocio.color_acento }}</small></label>
                        <label class="savix-pos-campo"><span>Tema</span><select v-model="negocio.tema_predeterminado" :disabled="!puede.configurar_negocio"><option value="sistema">Dispositivo</option><option value="claro">Claro</option><option value="oscuro">Oscuro</option></select></label>
                    </div>
                    <div class="savix-pos-seccion__subtitulo"><h2>Ticket e impuestos</h2><p>Los precios de catálogo son importes finales.</p></div>
                    <div class="savix-pos-cuadricula savix-pos-cuadricula--dos">
                        <label class="savix-pos-campo"><span>IVA porcentual</span><input v-model="negocio.porcentaje_iva" :disabled="!puede.configurar_negocio" type="number" min="0" max="99.99" step="0.01"></label>
                        <label class="savix-pos-campo"><span>Ancho de ticket</span><select v-model="negocio.ancho_ticket_mm" :disabled="!puede.configurar_negocio"><option :value="58">58 mm</option><option :value="80">80 mm</option></select></label>
                        <label class="savix-pos-campo savix-pos-campo--ancho"><span>Dirección en ticket</span><textarea v-model="negocio.direccion_ticket" :disabled="!puede.configurar_negocio" rows="2"></textarea></label>
                        <label class="savix-pos-campo savix-pos-campo--ancho"><span>Pie de ticket</span><textarea v-model="negocio.pie_ticket" :disabled="!puede.configurar_negocio" rows="2"></textarea></label>
                        <label class="savix-pos-opcion"><input v-model="negocio.precios_incluyen_iva" :disabled="!puede.configurar_negocio" type="checkbox"> Los precios incluyen IVA.</label>
                    </div>
                    <footer v-if="puede.configurar_negocio" class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="negocio.processing">{{ negocio.processing ? 'Guardando…' : 'Guardar configuración' }}</button></footer>
                </form>

                <section v-if="seccion === 'sucursales'" class="savix-pos-seccion">
                    <div class="savix-pos-seccion__titulo"><p class="savix-pos-eyebrow">SUCURSALES</p><h1>Operación independiente, catálogo compartido</h1><p>Cada sucursal activa solo los módulos que necesita.</p></div>
                    <div class="savix-pos-lista"><article v-for="item in sucursales" :key="item.id_sucursal" class="savix-pos-lista__fila"><div><strong>{{ item.nombre }}</strong><span>{{ item.clave }} · {{ item.activo ? 'Activa' : 'Inactiva' }}</span></div><div class="savix-pos-etiquetas"><span v-for="idModulo in item.modulos" :key="idModulo">{{ modulos.find((modulo) => modulo.id_modulo === idModulo)?.nombre }}</span></div><button v-if="puede.gestionar_sucursales" class="savix-pos-boton savix-pos-boton--secundario" @click="editarSucursal(item)">Editar</button></article></div>
                    <form v-if="puede.gestionar_sucursales" class="savix-pos-formulario-interno" @submit.prevent="guardarSucursal">
                        <div class="savix-pos-seccion__subtitulo"><h2>{{ editandoSucursal ? 'Editar sucursal' : 'Nueva sucursal' }}</h2><button v-if="editandoSucursal" class="savix-pos-enlace" type="button" @click="nuevaSucursal">Cancelar edición</button></div>
                        <div class="savix-pos-cuadricula savix-pos-cuadricula--dos"><label class="savix-pos-campo"><span>Nombre</span><input v-model="sucursal.nombre" required></label><label class="savix-pos-campo"><span>Clave</span><input v-model="sucursal.clave" maxlength="12" required></label><label class="savix-pos-campo"><span>Correo</span><input v-model="sucursal.correo" type="email"></label><label class="savix-pos-campo"><span>Teléfono</span><input v-model="sucursal.telefono"></label><label class="savix-pos-campo savix-pos-campo--ancho"><span>Dirección</span><textarea v-model="sucursal.direccion" rows="2"></textarea></label><label v-if="editandoSucursal" class="savix-pos-opcion"><input v-model="sucursal.activo" type="checkbox"> Sucursal activa.</label></div>
                        <fieldset class="savix-pos-seleccion"><legend>Módulos disponibles</legend><label v-for="modulo in modulos" :key="modulo.id_modulo"><input v-model="sucursal.modulos" :value="modulo.id_modulo" type="checkbox"> {{ modulo.nombre }}</label></fieldset>
                        <footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="sucursal.processing">{{ sucursal.processing ? 'Guardando…' : editandoSucursal ? 'Guardar sucursal' : 'Crear sucursal' }}</button></footer>
                    </form>
                </section>

                <section v-if="seccion === 'equipo'" class="savix-pos-seccion">
                    <div class="savix-pos-seccion__titulo"><p class="savix-pos-eyebrow">EQUIPO</p><h1>Accesos para cada función</h1><p>Todo usuario inicia asignado a una sucursal y a un rol.</p></div>
                    <div class="savix-pos-lista"><article v-for="item in usuarios" :key="item.id_usuario" class="savix-pos-lista__fila"><div><strong>{{ item.nombre }}</strong><span>@{{ item.nombre_usuario }} · {{ item.sucursal?.nombre || 'Sin sucursal' }}</span></div><div class="savix-pos-etiquetas"><span v-for="rol in item.roles" :key="rol">{{ rol }}</span><span :class="{ 'savix-pos-etiqueta--inactiva': !item.activo }">{{ item.activo ? 'Activo' : 'Inactivo' }}</span></div></article></div>
                    <form v-if="puede.gestionar_usuarios" class="savix-pos-formulario-interno" @submit.prevent="guardarUsuario"><div class="savix-pos-seccion__subtitulo"><h2>Nuevo usuario</h2><p>Entrega la contraseña y PIN de forma segura.</p></div><div class="savix-pos-cuadricula savix-pos-cuadricula--dos"><label class="savix-pos-campo"><span>Nombre completo</span><input v-model="usuario.nombre" required></label><label class="savix-pos-campo"><span>Usuario</span><input v-model="usuario.nombre_usuario" required></label><label class="savix-pos-campo"><span>Correo, opcional</span><input v-model="usuario.correo" type="email"></label><label class="savix-pos-campo"><span>Sucursal</span><select v-model="usuario.ref_sucursal"><option v-for="item in sucursales.filter((item) => item.activo)" :key="item.id_sucursal" :value="item.id_sucursal">{{ item.nombre }}</option></select></label><label class="savix-pos-campo"><span>Rol inicial</span><select v-model="usuario.ref_rol"><option v-for="rol in roles" :key="rol.id_rol" :value="rol.id_rol">{{ rol.nombre }}</option></select></label><label class="savix-pos-campo"><span>Contraseña</span><input v-model="usuario.contrasena" type="password" required></label><label class="savix-pos-campo"><span>Confirmar contraseña</span><input v-model="usuario.contrasena_confirmation" type="password" required></label><label class="savix-pos-campo"><span>PIN de 4 dígitos</span><input v-model="usuario.pin" type="password" inputmode="numeric" maxlength="4" required></label><label class="savix-pos-campo"><span>Confirmar PIN</span><input v-model="usuario.pin_confirmation" type="password" inputmode="numeric" maxlength="4" required></label></div><footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="usuario.processing">{{ usuario.processing ? 'Creando…' : 'Crear usuario' }}</button></footer></form>
                </section>

                <section v-if="seccion === 'permisos'" class="savix-pos-seccion">
                    <div class="savix-pos-seccion__titulo"><p class="savix-pos-eyebrow">PERMISOS</p><h1>Excepciones controladas</h1><p>Los roles definen el acceso base. Una denegación individual tiene prioridad.</p></div>
                    <div class="savix-pos-lista"><article v-for="item in usuarios.filter((item) => item.permisos_especiales.length)" :key="item.id_usuario" class="savix-pos-lista__fila"><div><strong>{{ item.nombre }}</strong><span v-for="especial in item.permisos_especiales" :key="especial.nombre">{{ especial.tipo_asignacion === 'permitir' ? 'Permite' : 'Deniega' }}: {{ especial.nombre }}. {{ especial.motivo }}</span></div></article><p v-if="!usuarios.some((item) => item.permisos_especiales.length)" class="savix-pos-vacio">Aún no hay excepciones de permisos.</p></div>
                    <form v-if="puede.gestionar_roles" class="savix-pos-formulario-interno" @submit.prevent="guardarPermiso"><div class="savix-pos-seccion__subtitulo"><h2>Asignar permiso especial</h2><p>El motivo queda registrado en la bitácora.</p></div><div class="savix-pos-cuadricula savix-pos-cuadricula--dos"><label class="savix-pos-campo"><span>Usuario</span><select v-model="permiso.ref_usuario" required><option disabled value="">Selecciona un usuario</option><option v-for="item in usuarios" :key="item.id_usuario" :value="item.id_usuario">{{ item.nombre }} · @{{ item.nombre_usuario }}</option></select></label><label class="savix-pos-campo"><span>Permiso</span><select v-model="permiso.ref_permiso"><option v-for="item in permisos" :key="item.id_permiso" :value="item.id_permiso">{{ item.modulo }} · {{ item.nombre }}</option></select></label><label class="savix-pos-campo"><span>Acción</span><select v-model="permiso.tipo_asignacion"><option value="permitir">Permitir</option><option value="denegar">Denegar</option></select></label><label class="savix-pos-campo"><span>Alcance</span><select v-model="permiso.ref_sucursal"><option value="">Todo el negocio</option><option v-for="item in sucursales" :key="item.id_sucursal" :value="item.id_sucursal">{{ item.nombre }}</option></select></label><label class="savix-pos-campo savix-pos-campo--ancho"><span>Motivo obligatorio</span><textarea v-model="permiso.motivo" rows="3" required></textarea></label></div><footer class="savix-pos-seccion__acciones"><button class="savix-pos-boton savix-pos-boton--primario" :disabled="permiso.processing">{{ permiso.processing ? 'Guardando…' : 'Guardar permiso' }}</button></footer></form>
                </section>
            </section>
        </div>
    </main>
</template>

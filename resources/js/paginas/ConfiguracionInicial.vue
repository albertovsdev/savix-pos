<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const formulario = useForm({
    nombre_negocio: '',
    nombre_comercial: '',
    clave_folio: 'SX',
    nombre_sucursal: 'Matriz',
    clave_sucursal: 'SM',
    nombre_admin: '',
    nombre_usuario: '',
    correo: '',
    contrasena: '',
    contrasena_confirmation: '',
    pin: '',
    pin_confirmation: '',
});

const enviar = () => formulario.post('/configuracion-inicial');
</script>

<template>
    <Head title="Configurar instalación" />

    <main class="savix-pos-inicial">
        <header class="savix-pos-inicial__encabezado">
            <div class="savix-pos-marca">
                <span class="savix-pos-marca__sello">SI</span>
                <div>
                    <p class="savix-pos-marca__nombre">SAVIX POS</p>
                    <p class="savix-pos-marca__subtitulo">Configuración inicial</p>
                </div>
            </div>
            <p>Una instalación, un negocio. Podrás agregar sucursales después.</p>
        </header>

        <form class="savix-pos-inicial__formulario" @submit.prevent="enviar">
            <section>
                <p class="savix-pos-eyebrow">01 · NEGOCIO</p>
                <h1>Comencemos por tu operación.</h1>
                <div class="savix-pos-cuadricula savix-pos-cuadricula--dos">
                    <label class="savix-pos-campo">
                        <span>Razón o nombre del negocio</span>
                        <input v-model="formulario.nombre_negocio" required>
                        <small v-if="formulario.errors.nombre_negocio" class="savix-pos-error">{{ formulario.errors.nombre_negocio }}</small>
                    </label>
                    <label class="savix-pos-campo">
                        <span>Nombre que verá el cliente</span>
                        <input v-model="formulario.nombre_comercial" required>
                        <small v-if="formulario.errors.nombre_comercial" class="savix-pos-error">{{ formulario.errors.nombre_comercial }}</small>
                    </label>
                    <label class="savix-pos-campo">
                        <span>Clave de folio</span>
                        <input v-model="formulario.clave_folio" maxlength="12" required>
                        <small>Ejemplo: SX-SM-0001</small>
                    </label>
                </div>
            </section>

            <section>
                <p class="savix-pos-eyebrow">02 · SUCURSAL</p>
                <h2>Registra el primer punto de operación.</h2>
                <div class="savix-pos-cuadricula savix-pos-cuadricula--dos">
                    <label class="savix-pos-campo">
                        <span>Nombre de sucursal</span>
                        <input v-model="formulario.nombre_sucursal" required>
                    </label>
                    <label class="savix-pos-campo">
                        <span>Clave de sucursal</span>
                        <input v-model="formulario.clave_sucursal" maxlength="12" required>
                    </label>
                </div>
            </section>

            <section>
                <p class="savix-pos-eyebrow">03 · ADMINISTRACIÓN</p>
                <h2>Crea el acceso del administrador.</h2>
                <div class="savix-pos-cuadricula savix-pos-cuadricula--dos">
                    <label class="savix-pos-campo">
                        <span>Nombre completo</span>
                        <input v-model="formulario.nombre_admin" autocomplete="name" required>
                    </label>
                    <label class="savix-pos-campo">
                        <span>Usuario</span>
                        <input v-model="formulario.nombre_usuario" autocomplete="username" required>
                        <small v-if="formulario.errors.nombre_usuario" class="savix-pos-error">{{ formulario.errors.nombre_usuario }}</small>
                    </label>
                    <label class="savix-pos-campo">
                        <span>Correo, opcional</span>
                        <input v-model="formulario.correo" type="email" autocomplete="email">
                        <small v-if="formulario.errors.correo" class="savix-pos-error">{{ formulario.errors.correo }}</small>
                    </label>
                    <label class="savix-pos-campo">
                        <span>Contraseña</span>
                        <input v-model="formulario.contrasena" type="password" autocomplete="new-password" required>
                        <small v-if="formulario.errors.contrasena" class="savix-pos-error">{{ formulario.errors.contrasena }}</small>
                    </label>
                    <label class="savix-pos-campo">
                        <span>Confirmar contraseña</span>
                        <input v-model="formulario.contrasena_confirmation" type="password" autocomplete="new-password" required>
                    </label>
                    <label class="savix-pos-campo">
                        <span>PIN de 4 dígitos</span>
                        <input v-model="formulario.pin" type="password" inputmode="numeric" maxlength="4" required>
                        <small>Autoriza descuentos, cancelaciones y otras acciones sensibles.</small>
                        <small v-if="formulario.errors.pin" class="savix-pos-error">{{ formulario.errors.pin }}</small>
                    </label>
                    <label class="savix-pos-campo">
                        <span>Confirmar PIN</span>
                        <input v-model="formulario.pin_confirmation" type="password" inputmode="numeric" maxlength="4" required>
                    </label>
                </div>
            </section>

            <footer class="savix-pos-inicial__acciones">
                <p>La información puede editarse más adelante desde Configuración.</p>
                <button class="savix-pos-boton savix-pos-boton--primario" :disabled="formulario.processing">
                    {{ formulario.processing ? 'Creando instalación…' : 'Crear SAVIX POS' }}
                </button>
            </footer>
        </form>
    </main>
</template>

<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const formulario = useForm({
    nombre_usuario: '',
    contrasena: '',
    recordar: false,
});

const enviar = () => formulario.post('/acceso');
</script>

<template>
    <Head title="Acceso" />

    <main class="savix-pos-acceso">
        <section class="savix-pos-acceso__marca">
            <div class="savix-pos-marca">
                <span class="savix-pos-marca__sello">SI</span>
                <div>
                    <p class="savix-pos-marca__nombre">SAVIX POS</p>
                    <p class="savix-pos-marca__subtitulo">Operación diaria, bajo control</p>
                </div>
            </div>

            <div class="savix-pos-acceso__mensaje">
                <p class="savix-pos-eyebrow">ACCESO OPERATIVO</p>
                <h1>Todo lo que ocurre en tu negocio, en su lugar.</h1>
                <p>Ingresa con tu usuario para abrir ventas, preparar pedidos o administrar la operación.</p>
            </div>
        </section>

        <section class="savix-pos-acceso__formulario" aria-labelledby="titulo-acceso">
            <form class="savix-pos-formulario" @submit.prevent="enviar">
                <div>
                    <p class="savix-pos-eyebrow">SAVIX POS</p>
                    <h2 id="titulo-acceso">Iniciar sesión</h2>
                    <p class="savix-pos-texto-secundario">Usa las credenciales asignadas por tu administrador.</p>
                </div>

                <label class="savix-pos-campo">
                    <span>Usuario</span>
                    <input v-model="formulario.nombre_usuario" autocomplete="username" autofocus required>
                    <small v-if="formulario.errors.nombre_usuario" class="savix-pos-error">{{ formulario.errors.nombre_usuario }}</small>
                </label>

                <label class="savix-pos-campo">
                    <span>Contraseña</span>
                    <input v-model="formulario.contrasena" type="password" autocomplete="current-password" required>
                    <small v-if="formulario.errors.contrasena" class="savix-pos-error">{{ formulario.errors.contrasena }}</small>
                </label>

                <label class="savix-pos-opcion">
                    <input v-model="formulario.recordar" type="checkbox">
                    <span>Mantener esta sesión en este dispositivo</span>
                </label>

                <button class="savix-pos-boton savix-pos-boton--primario" :disabled="formulario.processing">
                    {{ formulario.processing ? 'Verificando acceso…' : 'Entrar al sistema' }}
                </button>
            </form>
        </section>
    </main>
</template>

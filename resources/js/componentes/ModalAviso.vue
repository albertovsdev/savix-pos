<script setup>
defineProps({
    abierto: { type: Boolean, default: false },
    titulo: { type: String, required: true },
    mensaje: { type: String, required: true },
    tipo: { type: String, default: 'informacion' },
});

defineEmits(['cerrar']);
</script>

<template>
    <Teleport to="body">
        <div v-if="abierto" class="savix-pos-modal" role="presentation" @click.self="$emit('cerrar')">
            <section class="savix-pos-modal__dialogo" :class="`savix-pos-modal__dialogo--${tipo}`" role="alertdialog" aria-modal="true" :aria-label="titulo">
                <p class="savix-pos-modal__tipo">{{ tipo === 'error' ? 'REVISAR ACCIÓN' : tipo === 'exito' ? 'OPERACIÓN CONFIRMADA' : 'INFORMACIÓN' }}</p>
                <h2>{{ titulo }}</h2>
                <p>{{ mensaje }}</p>
                <footer><button class="savix-pos-boton savix-pos-boton--primario" type="button" autofocus @click="$emit('cerrar')">Entendido</button></footer>
            </section>
        </div>
    </Teleport>
</template>

<style scoped>
.savix-pos-modal { position: fixed; z-index: 1000; inset: 0; display: grid; place-items: center; padding: 20px; background: color-mix(in srgb, var(--savix-pos-texto) 42%, transparent); }
.savix-pos-modal__dialogo { width: min(100%, 440px); padding: 28px; border: 1px solid var(--savix-pos-borde); border-radius: 14px; color: var(--savix-pos-texto); background: var(--savix-pos-superficie); box-shadow: 0 24px 60px color-mix(in srgb, var(--savix-pos-texto) 24%, transparent); }
.savix-pos-modal__dialogo--error { border-color: color-mix(in srgb, var(--savix-pos-error) 48%, var(--savix-pos-borde)); }
.savix-pos-modal__tipo { margin: 0 0 12px; color: var(--savix-pos-marca); font-size: 11px; font-weight: 800; letter-spacing: .1em; }
.savix-pos-modal__dialogo h2 { margin: 0; font-size: 23px; line-height: 1.15; }
.savix-pos-modal__dialogo > p:not(.savix-pos-modal__tipo) { margin: 12px 0 24px; line-height: 1.55; }
.savix-pos-modal__dialogo footer { display: flex; justify-content: flex-end; }
</style>

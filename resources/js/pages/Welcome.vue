<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    BellRing,
    CalendarCheck,
    ChartColumn,
    Clock,
    Link2,
    MessageCircle,
    ShieldCheck,
    Smartphone,
    Users,
} from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { Button } from '@/components/ui/button';
import { dashboard, login, register } from '@/routes';
import { booking as publicBooking } from '@/routes/public';

// Negocio de demo creado por DemoBusinessSeeder.
const demoUrl = publicBooking('barberia-el-prado');

const businessTypes = [
    'Barberías',
    'Salones de belleza',
    'Consultorios',
    'Canchas',
    'Spas',
    'Estudios de tatuaje',
];

const features = [
    {
        icon: Link2,
        title: 'Tu link de reservas',
        text: 'Una página propia para compartir por WhatsApp o Instagram. Tus clientes reservan sin crear cuenta.',
    },
    {
        icon: ShieldCheck,
        title: 'Cero reservas dobles',
        text: 'Si dos personas intentan el mismo horario a la vez, solo una lo consigue. Garantizado por la base de datos.',
    },
    {
        icon: Clock,
        title: 'Horarios reales',
        text: 'Cada profesional con su horario, pausa de almuerzo y anticipación mínima. Solo se ofrece lo que está libre.',
    },
    {
        icon: Users,
        title: 'Todo tu equipo',
        text: 'Asigna qué servicios hace cada profesional. El cliente elige con quién o deja que asignemos al primero libre.',
    },
    {
        icon: MessageCircle,
        title: 'Contacto en un clic',
        text: 'Desde la agenda escribes al cliente por WhatsApp con el mensaje ya armado.',
    },
    {
        icon: ChartColumn,
        title: 'Tu negocio en números',
        text: 'Reservas del día, ingresos del mes y quién no se presentó, en un solo vistazo.',
    },
];

const steps = [
    {
        title: 'Crea tu negocio',
        text: 'Regístrate y carga tus servicios con duración y precio.',
    },
    {
        title: 'Define horarios',
        text: 'Agrega a tu equipo y marca los días y horas en que atienden.',
    },
    {
        title: 'Comparte tu link',
        text: 'Pégalo en tu bio o en tu estado de WhatsApp y empieza a recibir reservas.',
    },
];

const mockSlots = ['09:00', '09:30', '10:00', '10:30', '11:00', '11:30'];
</script>

<template>
    <Head title="Reservas online para tu negocio" />

    <div class="min-h-screen bg-background text-foreground">
        <!-- Hero -->
        <section
            class="relative overflow-hidden bg-sidebar text-sidebar-foreground"
        >
            <div
                class="pointer-events-none absolute -top-40 -left-32 size-[32rem] rounded-full bg-fire opacity-25 blur-3xl"
            />
            <div
                class="pointer-events-none absolute -right-40 bottom-0 size-[28rem] rounded-full bg-fire opacity-20 blur-3xl"
            />

            <header
                class="relative mx-auto flex max-w-6xl items-center justify-between px-4 py-5 sm:px-6"
            >
                <Link
                    href="/"
                    class="flex items-center gap-2 font-semibold text-white"
                >
                    <span
                        class="flex size-8 items-center justify-center rounded-md bg-fire"
                    >
                        <AppLogoIcon class="size-5 text-white" />
                    </span>
                    AgendaPro
                </Link>
                <nav class="flex items-center gap-2">
                    <Button v-if="$page.props.auth.user" as-child>
                        <Link :href="dashboard()">Ir a mi panel</Link>
                    </Button>
                    <template v-else>
                        <Button
                            variant="ghost"
                            class="text-sidebar-foreground hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                            as-child
                        >
                            <Link :href="login()">Iniciar sesión</Link>
                        </Button>
                        <Button class="hidden sm:inline-flex" as-child>
                            <Link :href="register()">Crear cuenta</Link>
                        </Button>
                    </template>
                </nav>
            </header>

            <div
                class="relative mx-auto grid max-w-6xl items-center gap-12 px-4 pt-10 pb-20 sm:px-6 lg:grid-cols-2 lg:pt-16 lg:pb-28"
            >
                <div>
                    <p
                        class="inline-flex items-center gap-2 rounded-full border border-sidebar-border bg-sidebar-accent px-3 py-1 text-xs font-medium text-sidebar-accent-foreground"
                    >
                        <CalendarCheck class="size-3.5" />
                        Reservas online 24/7
                    </p>
                    <h1
                        class="mt-6 text-4xl font-bold tracking-tight text-white sm:text-5xl lg:text-6xl"
                    >
                        Tu agenda llena,
                        <span class="text-fire">sin llamadas</span>
                        ni mensajes.
                    </h1>
                    <p class="mt-6 max-w-lg text-lg text-sidebar-foreground/75">
                        AgendaPro le da a tu negocio una página de reservas
                        donde tus clientes eligen servicio, profesional y
                        horario en segundos. Tú solo atiendes.
                    </p>
                    <div class="mt-8 flex flex-wrap gap-3">
                        <Button size="lg" as-child>
                            <Link :href="register()">
                                Crear mi agenda gratis <ArrowRight />
                            </Link>
                        </Button>
                        <Button
                            size="lg"
                            variant="outline"
                            class="border-sidebar-border bg-transparent text-white hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                            as-child
                        >
                            <Link :href="demoUrl">Probar la demo</Link>
                        </Button>
                    </div>
                    <p class="mt-4 text-sm text-sidebar-foreground/50">
                        Sin tarjeta · Configuración en 5 minutos
                    </p>
                </div>

                <!-- Vista previa del widget de reservas -->
                <div
                    class="relative mx-auto w-full max-w-sm"
                    aria-hidden="true"
                >
                    <div
                        class="absolute -inset-1 rounded-3xl bg-fire opacity-40 blur-xl"
                    />
                    <div
                        class="relative rounded-3xl border border-white/10 bg-[hsl(20_12%_9%)] p-6 shadow-2xl"
                    >
                        <p class="text-xs text-sidebar-foreground/60">
                            Barbería El Prado
                        </p>
                        <p class="mt-1 font-semibold text-white">
                            Corte clásico · 30 min
                        </p>
                        <div class="mt-5 flex gap-2">
                            <div
                                v-for="(day, i) in [
                                    'Lun 2',
                                    'Mar 3',
                                    'Mié 4',
                                    'Jue 5',
                                ]"
                                :key="day"
                                class="flex-1 rounded-xl border py-2 text-center text-xs"
                                :class="
                                    i === 1
                                        ? 'border-transparent bg-fire font-semibold text-white'
                                        : 'border-white/10 text-sidebar-foreground/70'
                                "
                            >
                                {{ day }}
                            </div>
                        </div>
                        <div class="mt-4 grid grid-cols-3 gap-2">
                            <div
                                v-for="(slot, i) in mockSlots"
                                :key="slot"
                                class="rounded-lg border py-2 text-center text-sm tabular-nums"
                                :class="
                                    i === 2
                                        ? 'border-transparent bg-primary font-semibold text-primary-foreground'
                                        : i === 4
                                          ? 'border-white/5 text-sidebar-foreground/25 line-through'
                                          : 'border-white/10 text-sidebar-foreground/80'
                                "
                            >
                                {{ slot }}
                            </div>
                        </div>
                        <div
                            class="mt-5 flex items-center justify-between border-t border-white/10 pt-4"
                        >
                            <span class="text-sm text-sidebar-foreground/60"
                                >Total</span
                            >
                            <span class="font-semibold text-white"
                                >Bs 50,00</span
                            >
                        </div>
                        <div
                            class="mt-4 rounded-lg bg-primary py-2.5 text-center text-sm font-semibold text-primary-foreground"
                        >
                            Confirmar reserva
                        </div>
                    </div>
                    <div
                        class="absolute -bottom-10 left-3 flex items-center gap-3 rounded-2xl border bg-card p-3 text-card-foreground shadow-xl sm:-left-8"
                    >
                        <span
                            class="flex size-9 items-center justify-center rounded-full bg-fire text-white"
                        >
                            <BellRing class="size-4" />
                        </span>
                        <span class="text-sm">
                            <span class="block font-medium">Nueva reserva</span>
                            <span class="text-xs text-muted-foreground"
                                >Ana · mar 10:00</span
                            >
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <!-- Tipos de negocio -->
        <section class="border-b">
            <div
                class="mx-auto flex max-w-6xl flex-wrap items-center justify-center gap-x-8 gap-y-3 px-4 py-6 text-sm text-muted-foreground sm:px-6"
            >
                <span class="font-medium text-foreground">Ideal para</span>
                <span v-for="type in businessTypes" :key="type">{{
                    type
                }}</span>
            </div>
        </section>

        <!-- Funcionalidades -->
        <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <div class="max-w-2xl">
                <p
                    class="text-sm font-semibold tracking-widest text-primary uppercase"
                >
                    Todo lo que necesitas
                </p>
                <h2 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">
                    Menos tiempo en el celular, más tiempo atendiendo.
                </h2>
            </div>
            <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="feature in features"
                    :key="feature.title"
                    class="rounded-2xl border bg-card p-6 transition-colors hover:border-primary/40"
                >
                    <span
                        class="flex size-10 items-center justify-center rounded-xl bg-accent text-primary"
                    >
                        <component :is="feature.icon" class="size-5" />
                    </span>
                    <h3 class="mt-4 font-semibold">{{ feature.title }}</h3>
                    <p class="mt-2 text-sm text-muted-foreground">
                        {{ feature.text }}
                    </p>
                </div>
            </div>
        </section>

        <!-- Cómo funciona -->
        <section class="bg-muted/50">
            <div class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
                <h2
                    class="text-center text-3xl font-bold tracking-tight sm:text-4xl"
                >
                    Lista en tres pasos
                </h2>
                <ol class="mt-12 grid gap-8 md:grid-cols-3">
                    <li
                        v-for="(step, i) in steps"
                        :key="step.title"
                        class="relative text-center"
                    >
                        <span
                            class="mx-auto flex size-12 items-center justify-center rounded-full bg-fire text-lg font-bold text-white shadow-lg shadow-orange-500/20"
                        >
                            {{ i + 1 }}
                        </span>
                        <h3 class="mt-4 font-semibold">{{ step.title }}</h3>
                        <p
                            class="mx-auto mt-2 max-w-xs text-sm text-muted-foreground"
                        >
                            {{ step.text }}
                        </p>
                    </li>
                </ol>
            </div>
        </section>

        <!-- CTA final -->
        <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6">
            <div
                class="relative overflow-hidden rounded-3xl bg-sidebar px-6 py-14 text-center sm:px-12"
            >
                <div
                    class="pointer-events-none absolute -top-24 left-1/2 size-96 -translate-x-1/2 rounded-full bg-fire opacity-30 blur-3xl"
                />
                <Smartphone
                    class="relative mx-auto size-10 text-sidebar-accent-foreground"
                />
                <h2
                    class="relative mt-4 text-3xl font-bold tracking-tight text-white sm:text-4xl"
                >
                    Mira cómo reservan tus clientes
                </h2>
                <p
                    class="relative mx-auto mt-4 max-w-xl text-sidebar-foreground/70"
                >
                    Entra a la página de una barbería de prueba y haz una
                    reserva real, de principio a fin, en menos de un minuto.
                </p>
                <div class="relative mt-8 flex flex-wrap justify-center gap-3">
                    <Button size="lg" as-child>
                        <Link :href="demoUrl"
                            >Probar la demo <ArrowRight
                        /></Link>
                    </Button>
                    <Button
                        size="lg"
                        variant="outline"
                        class="border-sidebar-border bg-transparent text-white hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                        as-child
                    >
                        <Link :href="register()">Crear mi cuenta</Link>
                    </Button>
                </div>
            </div>
        </section>

        <footer class="border-t">
            <div
                class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:px-6"
            >
                <span class="flex items-center gap-2">
                    <span
                        class="flex size-6 items-center justify-center rounded bg-fire"
                    >
                        <AppLogoIcon class="size-3.5 text-white" />
                    </span>
                    AgendaPro · Hecho en La Paz, Bolivia
                </span>
                <span>Laravel · Vue · PostgreSQL</span>
            </div>
        </footer>
    </div>
</template>

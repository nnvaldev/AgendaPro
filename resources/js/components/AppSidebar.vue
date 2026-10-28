<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    CalendarDays,
    ExternalLink,
    LayoutGrid,
    Scissors,
    Store,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import { index as agenda } from '@/routes/agenda';
import { edit as businessSettings } from '@/routes/business';
import { booking as publicBooking } from '@/routes/public';
import { index as services } from '@/routes/services';
import { index as staff } from '@/routes/staff';
import type { NavItem } from '@/types';

const page = usePage();

const mainNavItems: NavItem[] = [
    { title: 'Inicio', href: dashboard(), icon: LayoutGrid },
    { title: 'Agenda', href: agenda(), icon: CalendarDays },
    { title: 'Servicios', href: services(), icon: Scissors },
    { title: 'Equipo', href: staff(), icon: Users },
    { title: 'Mi negocio', href: businessSettings(), icon: Store },
];

const footerNavItems = computed<NavItem[]>(() => {
    const business = page.props.business;

    return business
        ? [
              {
                  title: 'Ver página de reservas',
                  href: publicBooking(business.slug).url,
                  icon: ExternalLink,
              },
          ]
        : [];
});
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>

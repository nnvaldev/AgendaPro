export type BookingStatus =
    | 'pending'
    | 'confirmed'
    | 'cancelled'
    | 'completed'
    | 'no_show';

/** Reserva tal como la recibe el panel (App\Http\Resources\Panel\BookingResource). */
export type PanelBooking = {
    id: number;
    status: BookingStatus;
    status_label: string;
    starts_at: string;
    ends_at: string;
    service: string;
    staff_member_id: number;
    staff_member: string;
    customer_name: string;
    customer_email: string;
    customer_phone: string | null;
    notes: string | null;
    price_cents: number;
};

/** Perfil público del negocio (App\Http\Resources\BusinessResource). */
export type PublicBusiness = {
    name: string;
    slug: string;
    timezone: string;
    currency: string;
    phone: string | null;
    address: string | null;
    services: {
        id: number;
        name: string;
        description: string | null;
        duration_minutes: number;
        price_cents: number;
        staff_member_ids: number[];
    }[];
    staff: { id: number; name: string }[];
};

/** Reserva vista por el cliente (App\Http\Resources\BookingResource). */
export type PublicBooking = {
    status: BookingStatus;
    starts_at: string;
    ends_at: string;
    service: string;
    staff_member: string;
    customer_name: string;
    price_cents: number;
    currency: string;
    business: { name: string; slug: string };
    manage_token?: string;
};

export type Slot = {
    starts_at: string;
    ends_at: string;
    staff_member_id: number;
};

export type WeeklyBlock = {
    weekday: number;
    starts_at: string;
    ends_at: string;
};

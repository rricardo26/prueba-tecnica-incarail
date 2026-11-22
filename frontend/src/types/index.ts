export interface User {
    id: number;
    name: string;
    email: string;
    is_active: boolean;
    roles: string[];
    permissions: string[];
}

export interface RoleItem {
    id: number;
    name: string;
    permissions: string[];
}

export interface LoginResponse {
    access_token: string;
    token_type: string;
    user: User;
}

export interface Competition {
    id: number;
    external_id: number;
    name: string;
    code: string | null;
    area_name: string | null;
    emblem_url: string | null;
    last_synced_at: string | null;
    teams_count?: number;
    teams?: Team[];
}

export interface Player {
    id: number;
    external_id: number;
    name: string;
    position: string | null;
    shirt_number: number | null;
    team?: {
        id: number;
        name: string;
        tla: string | null;
    };
}

export interface Team {
    id: number;
    external_id: number;
    name: string;
    short_name: string | null;
    tla: string | null;
    crest_url: string | null;
    venue: string | null;
    players_count?: number;
    players?: Player[];
    competitions?: Competition[];
}
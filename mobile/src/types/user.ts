export interface UserProfile {
  id: number | null;
  name: string;
  email: string;
  phone?: string | null;
  cpfcnpj?: string | null;
  avatar_url?: string | null;
  is_active?: boolean;
  is_verified?: boolean;
  role_id?: number;
  role_name?: string;
  created_at?: string;
  updated_at?: string;
}

export interface UpdateProfilePayload {
  name: string;
  email: string;
}

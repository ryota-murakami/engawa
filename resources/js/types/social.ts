export type UserCard = {
    id: number;
    name: string;
    avatar_url: string | null;
    bio?: string | null;
};

export type Paginator<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    prev_page_url: string | null;
    next_page_url: string | null;
    total: number;
};

export type Visibility = 'members' | 'friends' | 'private';

export type DiaryCard = {
    id: number;
    title: string;
    body: string;
    visibility: Visibility;
    created_at: string;
    author: UserCard;
    comments_count: number;
    can_update: boolean;
    images?: { id: number; url: string }[];
};

export type CommentCard = {
    id: number;
    body: string;
    created_at: string;
    author: UserCard;
    can_delete: boolean;
};

export type Friendship = {
    id: number;
    status: 'pending' | 'accepted';
    requested_by: number;
};

export type FriendRequest = Friendship & { member: UserCard };

export type CommunityCard = {
    id: number;
    name: string;
    description: string;
    owner: UserCard;
    members_count: number;
    is_member?: boolean;
};

export type TopicCard = {
    id: number;
    title: string;
    created_at: string;
    author: UserCard;
    replies_count: number;
};

export type NotificationItem = {
    id: string;
    text: string;
    url: string;
    read_at: string | null;
    created_at: string;
};

export type Footprint = {
    id: number;
    visitor: UserCard;
    last_visited_at: string;
};

export type SocialSharedProps = {
    auth?: { user?: { id: number; name: string } | null };
    flash?: { success?: string; status?: string };
};

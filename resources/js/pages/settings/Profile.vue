<script setup lang="ts">
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';
import { Form, Head, Link, usePage } from '@inertiajs/vue3';

import DeleteUser from '@/components/DeleteUser.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem } from '@/types';
import { ref } from 'vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: edit().url,
    },
];

const page = usePage();
const user = page.props.auth.user;

const uploadingAvatar = ref(false);
const uploadError = ref<string | null>(null);

async function handleAvatarChange(e: Event) {
    const input = e.target as HTMLInputElement;
    if (!input.files || input.files.length === 0) return;
    const file = input.files[0];
    const formData = new FormData();
    formData.append('avatar', file);
    uploadingAvatar.value = true;
    uploadError.value = null;

    try {
        const response = await fetch('/settings/profile/avatar', {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': (document.head.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
            },
            body: formData,
        });
        if (!response.ok) {
            const data = await response.json().catch(() => ({}));
            throw new Error(data.message ?? 'Upload failed');
        }
        const data = await response.json();
        // Update the current user's avatar in page props
        if (page.props.auth && page.props.auth.user) {
            page.props.auth.user.avatar = data.avatar ?? null;
        }
    } catch (err: any) {
        uploadError.value = err?.message ?? 'An error occurred while uploading the avatar';
        console.error(err);
    } finally {
        uploadingAvatar.value = false;
        // clear the input value to allow re-uploading the same file if needed
        (e.target as HTMLInputElement).value = '';
    }
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall
                    title="Profile information"
                    description="Update your name and email address"
                />

                <Form
                    v-bind="ProfileController.update.form()"
                    class="space-y-6"
                    v-slot="{ errors, processing, recentlySuccessful }"
                >
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input
                            id="name"
                            class="mt-1 block w-full"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Full name"
                        />
                        <InputError class="mt-2" :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="errors.email" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="avatar">Avatar</Label>
                        <div class="flex items-center gap-4">
                            <img
                                v-if="user?.avatar"
                                :src="user.avatar"
                                alt="Current avatar"
                                class="h-12 w-12 rounded-full object-cover"
                            />
                            <input
                                id="avatar"
                                type="file"
                                accept="image/*"
                                @change="handleAvatarChange"
                            />
                        </div>
                        <p v-if="uploadingAvatar" class="text-sm text-muted-foreground">
                            Uploading avatar...
                        </p>
                        <p v-if="uploadError" class="text-sm text-red-600">
                            {{ uploadError }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            PNG, JPG, or WEBP up to 5MB.
                        </p>
                    </div>

                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="send()"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500"
                            >
                                Click here to resend the verification email.
                            </Link>
                        </p>

                        <div
                            v-if="status === 'verification-link-sent'"
                            class="mt-2 text-sm font-medium text-green-600"
                        >
                            A new verification link has been sent to your email
                            address.
                        </div>
                    </div>

                    <div class="flex items-center gap-4">
                        <Button
                            :disabled="processing"
                            data-test="update-profile-button"
                            >Save</Button
                        >

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p
                                v-show="recentlySuccessful"
                                class="text-sm text-neutral-600"
                            >
                                Saved.
                            </p>
                        </Transition>
                    </div>
                </Form>
            </div>

            <DeleteUser />
        </SettingsLayout>
    </AppLayout>
</template>

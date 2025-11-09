<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import TwoFactorAuthenticatorApp from '@/components/TwoFactorAuthenticatorApp.vue';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref, provide, Ref, computed, ComputedRef } from 'vue';
import { Card } from '@/components/ui/card';
import TwoFactorRecoveryCodes from '@/components/TwoFactorRecoveryCodes.vue';
import TwoFactorEmail from '@/components/TwoFactorEmail.vue';
import TwoFactorPasskeys from '@/components/TwoFactorPasskeys.vue';

export interface authenticatorApp {
    userEnabled: boolean;
    requirePasswordConfirmation: boolean;
    requiresConfirmation: boolean;
}

export interface email {
    userEnabled: boolean;
    requirePasswordConfirmation: boolean;
    requiresConfirmation: boolean;
}

export interface passkeys {
    userEnabled: boolean;
    requirePasswordConfirmation: boolean;
}

export interface recoveryCodes {
    userEnabled: boolean;
    confirmsPasswordRecoveryCode: boolean;
}

interface canManageTwoFactorAuthentication {
    authenticatorApp: authenticatorApp;
    email: email;
    passkeys: passkeys;
}

interface canManageAdditionalAuthentication {
    recoveryCodes: recoveryCodes;
}

interface Props {
    userEnabledtwoFactor: boolean,
    canManageTwoFactorAuthentication: canManageTwoFactorAuthentication | false,
    canManageAdditionalAuthentication: canManageAdditionalAuthentication | false,
    recoveryCodesRequireTwoFactorEnabled: boolean,
}

const props = withDefaults(defineProps<Props>(), {
    userEnabledtwoFactor: false,
    canManageTwoFactorAuthentication: false,
    canManageAdditionalAuthentication: false,
    recoveryCodesRequireTwoFactorEnabled: false,
});

const recoveryCodesDialog = ref<boolean>(false);

const showRecoveryCodes = () => {
    return props.canManageAdditionalAuthentication && 
        props.canManageAdditionalAuthentication.recoveryCodes && 
        props.recoveryCodesRequireTwoFactorEnabled ? props.userEnabledtwoFactor : true;
}

provide<{
    recoveryCodesDialog: Ref<boolean, boolean>;
    authenticatorAppFeature: ComputedRef<authenticatorApp | false>;
    emailFeature: ComputedRef<email | false>;
    passkeysFeature: ComputedRef<passkeys | false>;
    recoveryCodesFeature: ComputedRef<recoveryCodes | false>;
    recoveryCodesRequireTwoFactorEnabled: boolean;
}>("context", {
    authenticatorAppFeature:computed(()=> props.canManageTwoFactorAuthentication && props.canManageTwoFactorAuthentication.authenticatorApp),
    emailFeature: computed(()=> props.canManageTwoFactorAuthentication && props.canManageTwoFactorAuthentication.email),
    passkeysFeature: computed(()=> props.canManageTwoFactorAuthentication && props.canManageTwoFactorAuthentication.passkeys),
    recoveryCodesFeature: computed(()=> props.canManageAdditionalAuthentication && props.canManageAdditionalAuthentication.recoveryCodes),
    recoveryCodesRequireTwoFactorEnabled: props.recoveryCodesRequireTwoFactorEnabled,
    recoveryCodesDialog: recoveryCodesDialog,
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Two-Factor Authentication',
        href: '/settings/two-factor-authentication',
    },
];

</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Two-Factor Authentication" />
        <SettingsLayout>

            <div v-if="canManageTwoFactorAuthentication" class="space-y-6">
                <HeadingSmall
                    title="Two-Factor Authentication"
                    description="Manage your two-factor authentication settings"
                />
                
                <Card class="p-4">
                    <TwoFactorAuthenticatorApp v-if="canManageTwoFactorAuthentication.authenticatorApp"/>

                    <TwoFactorEmail v-if="canManageTwoFactorAuthentication.email"/>

                    <TwoFactorPasskeys v-if="canManageTwoFactorAuthentication.passkeys"/>
                </Card>
            </div>

            <div v-if="showRecoveryCodes()" class="space-y-6">
                <HeadingSmall
                    title="Additional Authentication"
                    description="Manage your additional authentication settings"
                />
                
                <Card class="p-4">
                    <TwoFactorRecoveryCodes />
                </Card>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>

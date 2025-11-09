<script setup lang="ts">
import { passkeys } from '@/pages/settings/TwoFactor.vue';
import passkeysTwoFactor from '@/routes/passkeys-two-factor';
import { router, useForm } from '@inertiajs/vue3';
import { browserSupportsWebAuthn, startRegistration } from '@simplewebauthn/browser';
import { ComputedRef, inject, nextTick, ref, Ref, watch } from 'vue';
import axios from 'axios';
import passwordConfirmation from '@/routes/password-confirmation';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import InputError from '@/components/InputError.vue';
import ConfirmPasswordDialog from './ConfirmPasswordDialog.vue';
import { KeyRound, SquarePen, Trash2 } from 'lucide-vue-next';
import { Switch } from '@headlessui/vue';
import { Input } from '@/components/ui/input';

interface UpdatingPasskeyIdObject {
    passkeyBeingUpdated: Passkey;
}

function isUpdatingPasskeyIdObject(obj: any):
    obj is UpdatingPasskeyIdObject {
    return obj &&
        typeof obj.passkeyBeingUpdated === 'object';
}

interface DeletingPasskeyIdObject {
    passkeyBeingDeleted: Passkey;
}

function isDeletingPasskeyIdObject(obj: any):
    obj is DeletingPasskeyIdObject {
    return obj &&
        typeof obj.passkeyBeingDeleted === 'object';
}

interface Passkey {
    id: number
    name: string
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

const context = inject<{
    recoveryCodesDialog: Ref<boolean, boolean>;
    passkeysFeature: ComputedRef<passkeys>;
    recoveryCodesRequireTwoFactorEnabled: boolean;
}>("context");

const status = ref<"showing" | "adding" | "disabling" | null>(null);

const confirmingPasswordDialog = ref<boolean>(false);

const passkeysArray = ref<Passkey[]>([]);

const twoFactorDialog = ref<"showingPasskeys" | "addingPasskey" | UpdatingPasskeyIdObject | DeletingPasskeyIdObject | null>(null);

const twoFactorAuthenticationEnabled = ref(context?.passkeysFeature.value.userEnabled);

let onPasswordConfimedMethod: "showPasskeys" | "disablePasskeys" | any = null;

const closeDialog = () => {
    if (context && !context.passkeysFeature.value.userEnabled) {
        twoFactorAuthenticationEnabled.value = false;
    }
    passkeysArray.value = [];
    twoFactorDialog.value = null;
}

const addPasskeyForm = useForm<{
    name: string;
    passkey: string;
}>({
    name: "",
    passkey: ""
})

const updatePasskeyForm = useForm<{
    name: string;
}>({
    name: "",
})

const removePasskeyForm = useForm({});

const submitAddPasskeyForm = async () => {
    status.value = "adding";

    if (! browserSupportsWebAuthn()) {
        addPasskeyForm.setError('name', "Your Browser Dosen't support passkeys");
        status.value = null;
        return; 
    }

    const optionsResponse = await axios.get(passkeysTwoFactor.getRegisterOptions().url,{
        params: {name: addPasskeyForm.data.name},
        validateStatus: (status) => [200, 422].includes(status)
    });
    
    
    if (optionsResponse.status === 422) {
        addPasskeyForm.setError('name', optionsResponse.data.errors.name);
        status.value = null;
        return; 
    }

    try {
        const passkey = await startRegistration({
            optionsJSON:optionsResponse.data
        });
        addPasskeyForm.passkey = JSON.stringify(passkey);

        await nextTick();

        addPasskeyForm.post(passkeysTwoFactor.store().url,{
            errorBag: "createPasskey",
            onSuccess: () => Promise.all([
                showPasskeys(true)
            ]),
            onFinish: () => {
                status.value = null;
            }
        });

    } catch (error) {
        addPasskeyForm.setError('name', "Passkey creation failed. please try again.");
        console.error(error);
        status.value = null;
    }
}

watch(twoFactorDialog, (value) => {
    if (isUpdatingPasskeyIdObject(value)) {
        updatePasskeyForm.name = value.passkeyBeingUpdated.name
    }
});

const submitUpdatePasskeyForm = () => {
    if(isUpdatingPasskeyIdObject(twoFactorDialog.value)) {
        
        updatePasskeyForm.put(passkeysTwoFactor.update(twoFactorDialog.value.passkeyBeingUpdated.id).url, {
            onSuccess: () => Promise.all([
                showPasskeys()
            ]),
        })
    }
}

const submitRemovePasskeyForm = () => {
    if(isDeletingPasskeyIdObject(twoFactorDialog.value)) {
        removePasskeyForm.delete(passkeysTwoFactor.destroy(twoFactorDialog.value.passkeyBeingDeleted.id).url, {
            onSuccess: () => Promise.all([
                showPasskeys()
            ]),
        })
    }
}

const openPasswordDialog = () => {
    if (context?.passkeysFeature.value.requirePasswordConfirmation) {
        axios.get(passwordConfirmation.show().url).then(response => {
            if (response.data.confirmed) {
                onPasswordConfimed();
            } else {
                confirmingPasswordDialog.value = true;
            }
        });
    } else {
        onPasswordConfimed();
    }
    
};

const onPasswordConfimed = () => {
    if (onPasswordConfimedMethod === 'showPasskeys') {
        showPasskeys();
    } else if (onPasswordConfimedMethod === 'disablePasskeys') {
        disablePasskeys();
    }
}

const showPasskeys = async (isPasskeyAddedRecently:boolean = false) => {
    status.value = "showing";

    try {
        const {data} = await axios.get(passkeysTwoFactor.get().url);

        twoFactorAuthenticationEnabled.value = true;

        passkeysArray.value = data.passkeys;

        twoFactorDialog.value = "showingPasskeys";

        if (isPasskeyAddedRecently && context && context.recoveryCodesRequireTwoFactorEnabled && data.passkeys.length === 1)
            context.recoveryCodesDialog.value = true;

    } catch (error) {
        console.error(error)
    }

    status.value = null;
};

const disablePasskeys = () => {
    status.value = "disabling";

    router.delete(passkeysTwoFactor.disable().url, {
        onSuccess: () => twoFactorAuthenticationEnabled.value = false,
        onFinish: () => status.value = null
    })
};

const checkboxChanged = () => {
    
    if (! twoFactorAuthenticationEnabled.value) {
        onPasswordConfimedMethod = "showPasskeys"
        openPasswordDialog();
    } else {
        onPasswordConfimedMethod = "disablePasskeys"
        openPasswordDialog();
    }
}

</script>

<template>
    <div>
        <h3 class="text-base font-medium text-gray-900 dark:text-gray-100 flex items-center justify-between">
            <span>Passkeys</span>

            <Switch
                v-on:click="(e: PointerEvent) => {
                    if ((status === 'showing') || (status === 'disabling')) {
                        e.preventDefault();
                        return;
                    } 
                    checkboxChanged();
                }"
                :defaultChecked="twoFactorAuthenticationEnabled"
                :class="(twoFactorAuthenticationEnabled ? 'bg-blue-600' : 'bg-gray-200 dark:bg-gray-700') + 
                    (((status === 'showing') || (status === 'disabling')) ? ' opacity-50' : '') "
                class="inline-flex h-6 w-11 items-center rounded-full transition cursor-pointer"
                >
                <span
                    aria-hidden="true"
                    :class="twoFactorAuthenticationEnabled ? 'translate-x-6' : 'translate-x-0'"
                    class="size-4 translate-x-1 rounded-full bg-white transition"
                />
            </Switch>
        </h3>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">Use a passkey that inserts into your computer or syncs to your mobile device when you log in. You’ll need to use a supported mobile device or web browser.</p>

        <div v-if="browserSupportsWebAuthn() && context?.passkeysFeature.value.userEnabled" class="mt-5.5 flex items-center gap-4.5">
            <Button variant="default" @click="() => {
                onPasswordConfimedMethod = 'showPasskeys'
                openPasswordDialog();
            }">
                Manage your passkeys
            </Button>
        </div>

        <p v-if="! browserSupportsWebAuthn()" class="mt-1 text-sm text-red-500">Your browser doesn’t currently support passkeys.</p>

        <Dialog v-if="browserSupportsWebAuthn()" :open="twoFactorDialog === 'showingPasskeys'" @update:open="!$event && closeDialog()">
            <DialogContent class="sm:max-w-md">
                <DialogHeader class="flex items-center justify-center">
                    <div
                        class="mb-3 w-auto rounded-full border border-border bg-card p-0.5 shadow-sm"
                    >
                        <div
                            class="relative overflow-hidden rounded-full border border-border bg-muted p-2.5"
                        >
                            <div
                                class="absolute inset-0 grid grid-cols-5 opacity-50"
                            >
                                <div
                                    v-for="i in 5"
                                    :key="`col-${i}`"
                                    class="border-r border-border last:border-r-0"
                                />
                            </div>
                            <div
                                class="absolute inset-0 grid grid-rows-5 opacity-50"
                            >
                                <div
                                    v-for="i in 5"
                                    :key="`row-${i}`"
                                    class="border-b border-border last:border-b-0"
                                />
                            </div>
                            <KeyRound
                                class="relative z-20 size-6 text-foreground"
                            />
                        </div>
                    </div>

                    <DialogTitle>
                        Passkeys
                    </DialogTitle>

                    <DialogDescription class="text-center">
                        You need at least one passkey for this feature to be enabled
                    </DialogDescription>

                </DialogHeader>

                <div class="flex flex-col items-center space-y-5">
                    <div class="shadow dark:!shadow-none overflow-hidden dark:border dark:border-white/20 rounded-lg w-full">
                        
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                            <thead class="bg-white dark:bg-slate-900">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                        Name
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                        Actions
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-gray-50 dark:bg-[#171717]">
                                <tr v-if="passkeysArray.length === 0">
                                    <td colspan="3" class="px-6 py-4 whitespace-nowrap text-center text-gray-900 dark:text-gray-100">
                                        <em>No keys registered yet</em>
                                    </td>
                                </tr>

                                <tr v-for="key in passkeysArray" :key="key.id">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col items-start">
                                            <strong class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                                {{ key.name }}
                                            </strong>
                                            <span class="text-xs">
                                                Last use: {{ " " + (key.updated_at ?? "-") }}
                                            </span>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center space-x-3.5">
                                            <button title="Delete" @click="() => twoFactorDialog = {passkeyBeingUpdated:key}" class="hover:text-primary">
                                                <SquarePen class="size-4.5" strokeWidth={1.5} stroke="currentColor" fill="none"/>
                                            </button>

                                            <button title="Delete" @click="() => twoFactorDialog = {passkeyBeingDeleted:key}" class="hover:text-primary">
                                                <Trash2 class="size-4.5" strokeWidth={1.5} stroke="currentColor" fill="none"/>
                                            </button>
                                        </div>

                                    </td>
                                </tr>

                            </tbody>
                        </table>

                    </div>

                    <div class="flex w-full space-x-5">
                        <Button
                            type="button"
                            variant="outline"
                            class="flex-1"
                            @click="() => closeDialog()"
                        >
                            Close
                        </Button>
                        <Button
                            type="button"
                            class="flex-1"
                            @click="() => {
                                twoFactorDialog = 'addingPasskey';
                            }"
                        >
                            Add a new key
                        </Button>
                    </div>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-if="browserSupportsWebAuthn()" :open="twoFactorDialog === 'addingPasskey'" @update:open="!$event && closeDialog()">
            <DialogContent class="sm:max-w-md">
                <DialogHeader class="flex items-center justify-center">
                    <div
                        class="mb-3 w-auto rounded-full border border-border bg-card p-0.5 shadow-sm"
                    >
                        <div
                            class="relative overflow-hidden rounded-full border border-border bg-muted p-2.5"
                        >
                            <div
                                class="absolute inset-0 grid grid-cols-5 opacity-50"
                            >
                                <div
                                    v-for="i in 5"
                                    :key="`col-${i}`"
                                    class="border-r border-border last:border-r-0"
                                />
                            </div>
                            <div
                                class="absolute inset-0 grid grid-rows-5 opacity-50"
                            >
                                <div
                                    v-for="i in 5"
                                    :key="`row-${i}`"
                                    class="border-b border-border last:border-b-0"
                                />
                            </div>
                            <KeyRound
                                class="relative z-20 size-6 text-foreground"
                            />
                        </div>
                    </div>

                    <DialogTitle>
                        Create a new Passkey
                    </DialogTitle>

                    <DialogDescription class="text-center">
                        Please enter the name of the new passkey
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitAddPasskeyForm" class="flex flex-col items-center space-y-5">
                    <div class="relative w-full space-y-3">
                        <div class="flex w-full flex-col items-center space-y-3 py-2">
                            <Input
                                id="keyname"
                                type="text"
                                name="keyname"
                                class="w-full"
                                autofocus="true"
                                autocomplete='keyname'
                                placeholder="Key name"
                                v-model="addPasskeyForm.name"
                                :disabled="addPasskeyForm.processing"
                            />
                            <InputError :message="addPasskeyForm.errors.name" />
                            <InputError :message="addPasskeyForm.errors.passkey" />
                        </div>

                        <div class="flex w-full space-x-5">
                            <Button
                                type="button"
                                variant="outline"
                                class="flex-1"
                                @click="() => {
                                    twoFactorDialog = 'showingPasskeys';
                                }"
                                :disabled="addPasskeyForm.processing"
                            >
                                Back
                            </Button>
                            <Button
                                type="submit"
                                class="flex-1"
                                :disabled="addPasskeyForm.processing || status === 'adding'"
                            >
                                Add
                            </Button>
                        </div>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-if="browserSupportsWebAuthn()" :open="isUpdatingPasskeyIdObject(twoFactorDialog) ? twoFactorDialog.passkeyBeingUpdated != null : false" @update:open="!$event && closeDialog()">
            <DialogContent class="sm:max-w-md">
                <DialogHeader class="flex items-center justify-center">
                    <div
                        class="mb-3 w-auto rounded-full border border-border bg-card p-0.5 shadow-sm"
                    >
                        <div
                            class="relative overflow-hidden rounded-full border border-border bg-muted p-2.5"
                        >
                            <div
                                class="absolute inset-0 grid grid-cols-5 opacity-50"
                            >
                                <div
                                    v-for="i in 5"
                                    :key="`col-${i}`"
                                    class="border-r border-border last:border-r-0"
                                />
                            </div>
                            <div
                                class="absolute inset-0 grid grid-rows-5 opacity-50"
                            >
                                <div
                                    v-for="i in 5"
                                    :key="`row-${i}`"
                                    class="border-b border-border last:border-b-0"
                                />
                            </div>
                            <KeyRound
                                class="relative z-20 size-6 text-foreground"
                            />
                        </div>
                    </div>

                    <DialogTitle>
                        Update Passkey
                    </DialogTitle>

                    <DialogDescription class="text-center">
                        Update the name of this passkey
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitUpdatePasskeyForm" class="flex flex-col items-center space-y-5">
                    <div class="relative w-full space-y-3">
                        <div class="flex w-full flex-col items-center space-y-3 py-2">
                            <Input
                                id="keyname"
                                type="text"
                                name="keyname"
                                class="w-full"
                                autofocus="true"
                                autocomplete='keyname'
                                placeholder="Key name"
                                v-model="updatePasskeyForm.name"
                                :disabled="updatePasskeyForm.processing"
                            />
                            <InputError :message="updatePasskeyForm.errors.name" />
                        </div>

                        <div class="flex w-full space-x-5">
                            <Button
                                type="button"
                                variant="outline"
                                class="flex-1"
                                @click="() => {
                                    twoFactorDialog = 'showingPasskeys';
                                }"
                                :disabled="updatePasskeyForm.processing"
                            >
                                Back
                            </Button>
                            <Button
                                type="submit"
                                class="flex-1"
                                :disabled="updatePasskeyForm.processing || status === 'adding'"
                            >
                                Update
                            </Button>
                        </div>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-if="browserSupportsWebAuthn()" :open="isDeletingPasskeyIdObject(twoFactorDialog) ? twoFactorDialog.passkeyBeingDeleted != null : false" @update:open="!$event && closeDialog()">
            <DialogContent class="sm:max-w-md">
                <DialogHeader class="flex items-center justify-center">
                    <div
                        class="mb-3 w-auto rounded-full border border-border bg-card p-0.5 shadow-sm"
                    >
                        <div
                            class="relative overflow-hidden rounded-full border border-border bg-muted p-2.5"
                        >
                            <div
                                class="absolute inset-0 grid grid-cols-5 opacity-50"
                            >
                                <div
                                    v-for="i in 5"
                                    :key="`col-${i}`"
                                    class="border-r border-border last:border-r-0"
                                />
                            </div>
                            <div
                                class="absolute inset-0 grid grid-rows-5 opacity-50"
                            >
                                <div
                                    v-for="i in 5"
                                    :key="`row-${i}`"
                                    class="border-b border-border last:border-b-0"
                                />
                            </div>
                            <KeyRound
                                class="relative z-20 size-6 text-foreground"
                            />
                        </div>
                    </div>

                    <DialogTitle>
                        Delete Passkey
                    </DialogTitle>

                    <DialogDescription class="text-center">
                        Are you sure you would like to delete this passkey?
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitRemovePasskeyForm" class="flex flex-col items-center space-y-5">
                        
                    <div class="relative w-full space-y-3">
                        <div class="flex w-full space-x-5">
                            <Button
                                type="button"
                                variant="outline"
                                class="flex-1"
                                @click="() => {
                                    twoFactorDialog = 'showingPasskeys';
                                }"
                                :disabled="removePasskeyForm.processing"
                            >
                                Back
                            </Button>
                            <Button
                                type="submit"
                                class="flex-1"
                                variant="destructive"
                                :disabled="removePasskeyForm.processing || status === 'adding'"
                            >
                                Delete
                            </Button>
                        </div>
                    </div>
                </form>
            </DialogContent>
        </Dialog>

        <ConfirmPasswordDialog
            v-if="browserSupportsWebAuthn()"
            :is-open="confirmingPasswordDialog"
            @onPasswordConfimed="onPasswordConfimed"
            @toggle="confirmingPasswordDialog = !confirmingPasswordDialog"
        />
    </div>
</template>
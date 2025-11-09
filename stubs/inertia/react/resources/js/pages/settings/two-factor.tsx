import { type BreadcrumbItem } from '@/types';
import { Head, router, useForm } from '@inertiajs/react';

import HeadingSmall from '@/components/heading-small';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Switch } from '@headlessui/react';
import { createContext, SetStateAction, useContext, useEffect, useRef, useState } from 'react';
import { useClipboard } from '@/hooks/use-clipboard';

import axios from 'axios';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Clipboard, ClipboardCheck, SquarePen, Trash2, ScanLine, Loader2, Check, Copy, Mail, SquareDashedBottomCode, KeyRound } from 'lucide-react';
import ConfirmPasswordDialog from '@/components/confirm-password-dialog';
import authenticatorAppTwoFactor from '@/routes/authenticator-app-two-factor';
import passwordConfirmation from '@/routes/password-confirmation';
import emailTwoFactor from '@/routes/email-two-factor';
import twoFactorRecoveryCodes from '@/routes/two-factor-recovery-codes';
import { browserSupportsWebAuthn, startRegistration } from '@simplewebauthn/browser';

import passkeysTwoFactor from '@/routes/passkeys-two-factor';
import { InputOTP, InputOTPGroup, InputOTPSlot } from '@/components/ui/input-otp';
import { REGEXP_ONLY_DIGITS } from 'input-otp';
import { Card } from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Two-Factor Authentication',
        href: '/settings/two-factor-authentication',
    },
];

interface authenticatorApp {
    userEnabled: boolean;
    requirePasswordConfirmation: boolean;
    requiresConfirmation: boolean;
}

interface email {
    userEnabled: boolean;
    requirePasswordConfirmation: boolean;
    requiresConfirmation: boolean;
}

interface passkeys {
    userEnabled: boolean;
    requirePasswordConfirmation: boolean;
}

interface recoveryCodes {
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

const TwoFactorContext = createContext<{
    recoveryCodesDialog: boolean;
    setRecoveryCodesDialog: React.Dispatch<React.SetStateAction<boolean>>;
    authenticatorAppFeature: authenticatorApp | false;
    emailFeature: email | false;
    passkeysFeature: passkeys | false;
    recoveryCodesFeature: recoveryCodes | false;
    recoveryCodesRequireTwoFactorEnabled: boolean;
}>({
    authenticatorAppFeature: false,
    emailFeature: false,
    passkeysFeature: false,
    recoveryCodesFeature: false,
    recoveryCodesRequireTwoFactorEnabled: false,
    recoveryCodesDialog: false,
    setRecoveryCodesDialog: function (value: SetStateAction<boolean>): void {
        throw new Error('Function not implemented.');
    }
});

export default function TwoFactor({
    userEnabledtwoFactor = false,
    canManageTwoFactorAuthentication = false,
    canManageAdditionalAuthentication = false,
    recoveryCodesRequireTwoFactorEnabled = false,
}: {
    userEnabledtwoFactor: boolean,
    canManageTwoFactorAuthentication: canManageTwoFactorAuthentication | false,
    canManageAdditionalAuthentication: canManageAdditionalAuthentication | false,
    recoveryCodesRequireTwoFactorEnabled: boolean,
}) {

    const [recoveryCodesDialog, setRecoveryCodesDialog] = useState<boolean>(false)

    const showRecoveryCodes:boolean = canManageAdditionalAuthentication && 
                                    canManageAdditionalAuthentication.recoveryCodes && 
                                    recoveryCodesRequireTwoFactorEnabled ? userEnabledtwoFactor : true

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Two-Factor Authentication" />

            <TwoFactorContext.Provider value={{ 
                recoveryCodesDialog,
                setRecoveryCodesDialog,
                authenticatorAppFeature: canManageTwoFactorAuthentication && canManageTwoFactorAuthentication.authenticatorApp,
                emailFeature: canManageTwoFactorAuthentication && canManageTwoFactorAuthentication.email,
                passkeysFeature: canManageTwoFactorAuthentication && canManageTwoFactorAuthentication.passkeys,
                recoveryCodesFeature: canManageAdditionalAuthentication && canManageAdditionalAuthentication.recoveryCodes,
                recoveryCodesRequireTwoFactorEnabled: recoveryCodesRequireTwoFactorEnabled
            }}>
                <SettingsLayout>

                    {canManageTwoFactorAuthentication && (
                        <div className="space-y-6">
                            <HeadingSmall title="Two-Factor Authentication" description="Manage your two-factor authentication settings" />

                                <Card className='p-4'>
                                    {canManageTwoFactorAuthentication.authenticatorApp && (
                                        <AuthenticatorApp />
                                    )}

                                    {canManageTwoFactorAuthentication.email && (
                                        <Email />
                                    )}

                                    {canManageTwoFactorAuthentication.passkeys && (
                                        <Passkeys />
                                    )}
                                </Card>
                            
                        </div>
                    )}

                    {showRecoveryCodes && (
                        <div className="space-y-6">
                            <HeadingSmall title="Additional Authentication" description="Manage your additional authentication settings"/>
                            
                            <Card className='p-4'>
                                <RecoveryCodes />
                            </Card>
                        </div>
                    )}

                </SettingsLayout>
            </TwoFactorContext.Provider>
        </AppLayout>
    );
}


const DialogIcon = ({icon} : {icon: "scan" | "email" | "code" | "key"}) => {
    return (
        <div className="mb-3 rounded-full border border-border bg-card p-0.5 shadow-sm">
            <div className="relative overflow-hidden rounded-full border border-border bg-muted p-2.5">
                <div className="absolute inset-0 grid grid-cols-5 opacity-50">
                    {Array.from({ length: 5 }, (_, i) => (
                        <div
                            key={`col-${i + 1}`}
                            className="border-r border-border last:border-r-0"
                        />
                    ))}
                </div>
                <div className="absolute inset-0 grid grid-rows-5 opacity-50">
                    {Array.from({ length: 5 }, (_, i) => (
                        <div
                            key={`row-${i + 1}`}
                            className="border-b border-border last:border-b-0"
                        />
                    ))}
                </div>
                {icon === "scan" ? (
                    <ScanLine className="relative z-20 size-6 text-foreground" />
                ) : (icon === "email") ? (
                    <Mail className="relative z-20 size-6 text-foreground" />
                ) : (icon === "code") ? (
                    <SquareDashedBottomCode className="relative z-20 size-6 text-foreground" />
                ) : (icon === "key") ? (
                    <KeyRound className="relative z-20 size-6 text-foreground" />
                ) : null}
            </div>
        </div>
    );
}

const AuthenticatorApp = ({}) => {
    const {authenticatorAppFeature, recoveryCodesRequireTwoFactorEnabled, setRecoveryCodesDialog} = useContext(TwoFactorContext);

    const [status, setStatus] = useState<"enabling" | "confirming" | "disabling" | null>(null);

    const [qrCode, setQrCode] = useState(null);
    const [setupKey, setSetupKey] = useState(null);

    const [confirmingPasswordDialog, setConfirmingPasswordDialog] = useState<boolean>(false);

    const onPasswordConfimedMethod = useRef<"enableTwoFactorAuthentication" | "disableTwoFactorAuthentication" | "confirmTwoFactorAuthentication" | null>(null);

    const confirmationForm = useForm({
        code: '',
    });

    const [twoFactorAuthenticationEnabled, setTwoFactorAuthenticationEnabled] = useState(authenticatorAppFeature && authenticatorAppFeature.userEnabled);

    const [showVerificationStep, setShowVerificationStep] = useState<boolean>(false);

    const [copiedText, copy] = useClipboard();
    const IconComponent = copiedText === setupKey ? Check : Copy;

    const OTP_MAX_LENGTH = 6;

    const closeDialog = () =>{
        if (authenticatorAppFeature && authenticatorAppFeature.requiresConfirmation) {
            disableTwoFactorAuthentication();
        } else {
            setQrCode(null);
            setSetupKey(null);
        }

        if (showVerificationStep) {
            setShowVerificationStep(false);
        }
    }

    useEffect(() => {
        if (! twoFactorAuthenticationEnabled) {
            confirmationForm.resetAndClearErrors();
        }
    }, [twoFactorAuthenticationEnabled]);

    const enableTwoFactorAuthentication = () => {
        setStatus("enabling");
        setTimeout(() => {
            router.post(authenticatorAppTwoFactor.enable().url, {}, {
                preserveScroll: true,
                onSuccess: () => Promise.all([
                    showQrCode(),
                    showSetupKey(),
                    setTwoFactorAuthenticationEnabled(true),
                    setStatus((authenticatorAppFeature && authenticatorAppFeature.requiresConfirmation) ? "confirming" : null)
                ]),
                onError: () => {
                    setStatus(null)
                },

            });
        }, 0);
    };

    const confirmTwoFactorAuthentication = () => {
        confirmationForm.post(authenticatorAppTwoFactor.confirm().url, {
            errorBag: "confirmTwoFactorAuthentication",
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setStatus(null);
                setQrCode(null);
                setSetupKey(null);
                
                if (recoveryCodesRequireTwoFactorEnabled) {
                    setRecoveryCodesDialog(true);
                }
            },
        });
    };

    const disableTwoFactorAuthentication = () => {
        let previousStatus = status;
        
        setStatus("disabling");

        setTimeout(() => {
            router.delete(authenticatorAppTwoFactor.disable().url, {
                preserveScroll: true,
                onSuccess: () => {
                    setTwoFactorAuthenticationEnabled(false);
                    setStatus(null);
                },
                onError: () => {
                    if (previousStatus === "confirming") setStatus(previousStatus);
                }
            });
        }, 0);
    };

    const openPasswordDialog = () => {

        if (authenticatorAppFeature && authenticatorAppFeature.requirePasswordConfirmation) {
            axios.get(passwordConfirmation.show().url).then(response => {
                if (response.data.confirmed) {
                    onPasswordConfimed();
                } else {
                    setConfirmingPasswordDialog(true);
                }
            });
        } else {
            onPasswordConfimed();
        }
    };

    const onPasswordConfimed = () => {
        if (onPasswordConfimedMethod.current === 'enableTwoFactorAuthentication') {
            enableTwoFactorAuthentication();
        } else if (onPasswordConfimedMethod.current === 'disableTwoFactorAuthentication') {
            disableTwoFactorAuthentication();
        } else if (onPasswordConfimedMethod.current === 'confirmTwoFactorAuthentication') {
            confirmTwoFactorAuthentication();
        }
    }

    const showQrCode = async () => {
        const response = await axios.get(authenticatorAppTwoFactor.qrCode().url);
        setQrCode(response.data.svg);
    };

    const showSetupKey = async () => {
        const response = await axios.get(authenticatorAppTwoFactor.secretKey().url);
        setSetupKey(response.data.secretKey);
    }

    const checkboxChanged = (checked: boolean) => {
        if (checked) {
            onPasswordConfimedMethod.current = "enableTwoFactorAuthentication"
            openPasswordDialog();
        } else {
            onPasswordConfimedMethod.current = "disableTwoFactorAuthentication"
            openPasswordDialog();
        }
    }

    return (
        <div>
            <h3 className="text-base font-medium text-gray-900 dark:text-gray-100 flex items-center justify-between">
                <span>Authenticator app</span>
                
                <Switch
                    disabled={(status === "enabling") || (status === "disabling")}

                    checked={twoFactorAuthenticationEnabled}
                    onChange={(checked) => checkboxChanged(checked)}
                    className="group inline-flex h-6 w-11 items-center rounded-full bg-gray-200 dark:bg-gray-700 transition data-checked:bg-blue-600 data-checked:dark:bg-blue-600 cursor-pointer disabled:opacity-50"
                >
                    <span className="size-4 translate-x-1 rounded-full bg-white transition group-data-checked:translate-x-6" />
                </Switch>
            </h3>
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Use a mobile authenticator app to get a verification code to enter every time you log in</p>

            <Dialog open={twoFactorAuthenticationEnabled && (setupKey!=null) && (qrCode!=null)} onOpenChange={(open) => !open && closeDialog()}>
                <DialogContent className="sm:max-w-md">

                    <DialogHeader className="flex items-center justify-center">

                        <DialogIcon icon="scan" />

                        <DialogTitle>
                            {showVerificationStep ? (<>
                                Verify Authentication Code
                            </>) : (authenticatorAppFeature && authenticatorAppFeature.requiresConfirmation) ? (<>
                                Enable Two-Factor Authentication
                            </>) : (<>
                                Two-Factor Authentication Enabled
                            </>)}
                        </DialogTitle>
                        <DialogDescription className="text-center">
                            {showVerificationStep ? (<>
                                Enter the 6-digit code from your authenticator app
                            </>) : (authenticatorAppFeature && authenticatorAppFeature.requiresConfirmation) ? (<>
                                To finish enabling two-factor authentication, scan the QR code or enter the setup key in your authenticator app
                            </>) : (<>
                                Two-factor authentication is now enabled. Scan the following QR code using your phone's authenticator application or enter the setup key
                            </>)}
                        </DialogDescription>
                    </DialogHeader>
                    
                    
                    <div className="flex flex-col items-center space-y-5">
                        {showVerificationStep ? (<>
                            <div className="relative w-full space-y-3">
                                <div className="flex w-full flex-col items-center space-y-3 py-2">
                                    <InputOTP
                                        id="otp"
                                        name="otp"
                                        maxLength={OTP_MAX_LENGTH}
                                        onChange={(newValue) => confirmationForm.setData('code', newValue)}
                                        disabled={confirmationForm.processing}
                                        pattern={REGEXP_ONLY_DIGITS}
                                        onKeyDown={(e: KeyboardEvent ) => {
                                            if (e.key !== 'Enter') return;
                                            if (confirmationForm.processing || confirmationForm.data.code.length < OTP_MAX_LENGTH) return;
                                            confirmTwoFactorAuthentication();
                                        }}
                                        autoFocus={true}
                                    >
                                        <InputOTPGroup>
                                            {Array.from(
                                                { length: OTP_MAX_LENGTH },
                                                (_, index) => (
                                                    <InputOTPSlot
                                                        key={index}
                                                        index={index}
                                                    />
                                                ),
                                            )}
                                        </InputOTPGroup>
                                    </InputOTP>
                                    <InputError message={confirmationForm.errors.code} />
                                </div>

                                <div className="flex w-full space-x-5">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="flex-1"
                                        onClick={() => setShowVerificationStep(false)}
                                        disabled={confirmationForm.processing}
                                    >
                                        Back
                                    </Button>
                                    <Button
                                        type="button"
                                        className="flex-1"
                                        disabled={
                                            confirmationForm.processing || confirmationForm.data.code.length < OTP_MAX_LENGTH
                                        }
                                        onClick={() =>{
                                            onPasswordConfimedMethod.current = "confirmTwoFactorAuthentication"
                                            openPasswordDialog();
                                        }}
                                    >
                                        Confirm
                                    </Button>
                                </div>
                            </div>
                        </>) : (<>
                            <div className="mx-auto flex max-w-md overflow-hidden">
                                <div className="mx-auto aspect-square w-64 rounded-lg border border-border dark:bg-white">
                                    <div className="z-10 flex h-full w-full items-center justify-center p-5">
                                        {qrCode ? (
                                            <div
                                                dangerouslySetInnerHTML={{
                                                    __html: qrCode,
                                                }}
                                            />
                                        ) : (
                                            <Loader2 className="flex size-4 animate-spin" />
                                        )}
                                    </div>
                                </div>
                            </div>

                            {(authenticatorAppFeature && authenticatorAppFeature.requiresConfirmation) ? (
                                <div className="flex w-full space-x-5">
                                    <Button className="w-full" onClick={()=> setShowVerificationStep(true)}>
                                        Continue
                                    </Button>
                                </div>
                            ) : (
                                <div className="flex w-full space-x-5">
                                    <Button className="w-full" onClick={() => closeDialog()}>
                                        Close
                                    </Button>
                                </div>
                            )}

                            <div className="relative flex w-full items-center justify-center">
                                <div className="absolute inset-0 top-1/2 h-px w-full bg-border" />
                                <span className="relative bg-card px-2 py-1">
                                    or, enter the code manually
                                </span>
                            </div>

                            <div className="flex w-full space-x-2">
                                <div className="flex w-full items-stretch overflow-hidden rounded-xl border border-border">
                                    {!setupKey ? (
                                        <div className="flex h-full w-full items-center justify-center bg-muted p-3">
                                            <Loader2 className="size-4 animate-spin" />
                                        </div>
                                    ) : (
                                        <>
                                            <input
                                                type="text"
                                                readOnly
                                                value={setupKey}
                                                className="h-full w-full bg-background p-3 text-foreground outline-none"
                                            />
                                            <button
                                                onClick={() => copy(setupKey)}
                                                className="border-l border-border px-3 hover:bg-muted"
                                            >
                                                <IconComponent className="w-4" />
                                            </button>
                                        </>
                                    )}
                                </div>
                            </div>
                        </>)}
                    </div>
                </DialogContent>
            </Dialog>

            <ConfirmPasswordDialog
                confirmingPasswordDialog={confirmingPasswordDialog}
                setConfirmingPasswordDialog={setConfirmingPasswordDialog}
                onPasswordConfimed={onPasswordConfimed}
            />
        </div>
    );
}

const Email = ({}) => {
    const {emailFeature, recoveryCodesRequireTwoFactorEnabled, setRecoveryCodesDialog} = useContext(TwoFactorContext);

    const [status, setStatus] = useState<"enabling" | "confirming" | "disabling" | null>(null);

    const [confirmingPasswordDialog, setConfirmingPasswordDialog] = useState<boolean>(false);

    const [twoFactorDialog, setTwoFactorDialog] = useState<boolean>(false);

    const onPasswordConfimedMethod = useRef<"enableTwoFactorAuthentication" | "disableTwoFactorAuthentication" | "confirmTwoFactorAuthentication" | null>(null);

    const [twoFactorAuthenticationEnabled, setTwoFactorAuthenticationEnabled] = useState<boolean>((status !== "enabling") && emailFeature && emailFeature.userEnabled);

    const OTP_MAX_LENGTH = 6;

    const closeDialog = () =>{
        disableTwoFactorAuthentication();
    }

    const confirmationForm = useForm({
        code: '',
    });

    const sendCodeForm = useForm<{
        [key: string]: string | undefined
    }>({});

    useEffect(() => {
        if (! twoFactorAuthenticationEnabled) {
            confirmationForm.resetAndClearErrors();
        }
    }, [twoFactorAuthenticationEnabled]);

    const enableTwoFactorAuthentication = () => {
        setStatus("enabling");
        setTimeout(() => {
            router.post(emailTwoFactor.enable().url, {}, {
                preserveScroll: true,
                onSuccess: () => Promise.all([
                    setTwoFactorAuthenticationEnabled(true),
                    setTwoFactorDialog(true),
                    setStatus((emailFeature && emailFeature.requiresConfirmation) ? "confirming" : null)
                ]),
                onError: () => {    
                    setStatus(null);
                },
            });
        }, 0);
    };

    const sendCode = () =>{

        sendCodeForm.post(emailTwoFactor.sendCode().url, {
            errorBag: "EmailTwoFactorAuthenticationNotification",
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {

            },
        })
    }

    const confirmTwoFactorAuthentication = () => {
        confirmationForm.post(emailTwoFactor.confirm().url, {
            errorBag: "confirmEmailTwoFactorAuthentication",
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                setStatus(null);
                setTwoFactorDialog(false);

                if (recoveryCodesRequireTwoFactorEnabled) {
                    setRecoveryCodesDialog(true);
                }
            },
        });
    };

    const disableTwoFactorAuthentication = () => {
        let previousStatus = status;
        
        setStatus("disabling");
    
        setTimeout(() => {
            router.delete(emailTwoFactor.disable().url, {
                preserveScroll: true,
                onSuccess: () => {
                    setStatus(null);
                    setTwoFactorAuthenticationEnabled(false);
                    setTwoFactorDialog(false);
                },
                onError: () => {
                    if (previousStatus === "confirming") setStatus(previousStatus);
                }
            });
        }, 0);
    };

    const openPasswordDialog = () => {
        if (emailFeature && emailFeature.requirePasswordConfirmation) {
            axios.get(passwordConfirmation.show().url).then(response => {
                if (response.data.confirmed) {
                    onPasswordConfimed();
                } else {
                    setConfirmingPasswordDialog(true);
                }
            });
        } else {
            onPasswordConfimed();
        }
        
    };

    const onPasswordConfimed = () => {
        if (onPasswordConfimedMethod.current === 'enableTwoFactorAuthentication') {
            enableTwoFactorAuthentication();
        } else if (onPasswordConfimedMethod.current === 'disableTwoFactorAuthentication') {
            disableTwoFactorAuthentication();
        } else if (onPasswordConfimedMethod.current === 'confirmTwoFactorAuthentication') {
            confirmTwoFactorAuthentication();
        }
    }

    const checkboxChanged = (checked: boolean) => {

        if (checked) {
            onPasswordConfimedMethod.current = "enableTwoFactorAuthentication"
            openPasswordDialog(); 
        } else {
            onPasswordConfimedMethod.current = "disableTwoFactorAuthentication"
            openPasswordDialog();
        }

    }

    return (
        <div>
            <h3 className="text-base font-medium text-gray-900 dark:text-gray-100 flex items-center justify-between">
                <span>Email</span>
                
                <Switch
                    disabled={(status === "enabling") || (status === "disabling")}

                    checked={twoFactorAuthenticationEnabled}
                    onChange={(checked) => checkboxChanged(checked)}
                    className="group inline-flex h-6 w-11 items-center rounded-full bg-gray-200 dark:bg-gray-700 transition data-checked:bg-blue-600 data-checked:dark:bg-blue-600 cursor-pointer disabled:opacity-50"
                >
                    <span className="size-4 translate-x-1 rounded-full bg-white transition group-data-checked:translate-x-6" />
                </Switch>
            </h3>
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Use your email to receive an authentication code to enter when you log in.</p>

            <Dialog open={emailFeature && emailFeature.requiresConfirmation && twoFactorDialog} onOpenChange={(open) => !open && closeDialog()}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader className="flex items-center justify-center">
                        <DialogIcon icon="email" />
                        <DialogTitle>
                            Entar OTP Code
                        </DialogTitle>
                        <DialogDescription className="text-center">
                            Please confirm access to your account by entering the OTP that was sent to your email
                        </DialogDescription>

                        <DialogDescription className="text-center">
                            Didn't receive code? {'   '}
                            <button onClick={sendCode} className={"cursor-pointer text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current! dark:decoration-neutral-500 " + (sendCodeForm.processing ? "opacity-25" : "") } disabled={sendCodeForm.processing}>Resend</button>
                            <InputError message={sendCodeForm.errors.attempts} className="mt-2" />
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex flex-col items-center space-y-5">

                        <div className="relative w-full space-y-3">
                            <div className="flex w-full flex-col items-center space-y-3 py-2">
                                <InputOTP
                                    id="otp"
                                    name="code"
                                    maxLength={OTP_MAX_LENGTH}
                                    onChange={(newValue) => confirmationForm.setData('code', newValue)}
                                    disabled={confirmationForm.processing}
                                    pattern={REGEXP_ONLY_DIGITS}
                                    onKeyDown={(e) => {
                                        if (e.key !== 'Enter') return;
                                        if (confirmationForm.processing || confirmationForm.data.code.length < OTP_MAX_LENGTH) return;
                                        confirmTwoFactorAuthentication()
                                    }}
                                    autoFocus={true}
                                >
                                    <InputOTPGroup>
                                        {Array.from(
                                            { length: OTP_MAX_LENGTH },
                                            (_, index) => (
                                                <InputOTPSlot
                                                    key={index}
                                                    index={index}
                                                />
                                            ),
                                        )}
                                    </InputOTPGroup>
                                </InputOTP>
                                <InputError message={confirmationForm.errors.code} />
                            </div>

                            <div className="flex w-full space-x-5">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="flex-1"
                                    onClick={() => closeDialog()}
                                    disabled={confirmationForm.processing}
                                >
                                    Cancel
                                </Button>
                                <Button
                                    type="button"
                                    className="flex-1"
                                    onClick={(e) =>{
                                        onPasswordConfimedMethod.current = "confirmTwoFactorAuthentication"
                                        openPasswordDialog();
                                    }}
                                    disabled={
                                        confirmationForm.processing || confirmationForm.data.code.length < OTP_MAX_LENGTH
                                    }
                                >
                                    Confirm
                                </Button>
                            </div>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <ConfirmPasswordDialog
                confirmingPasswordDialog={confirmingPasswordDialog}
                setConfirmingPasswordDialog={setConfirmingPasswordDialog}
                onPasswordConfimed={onPasswordConfimed}
            />
        </div>
    );
}

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

const Passkeys = ({}) => {
    const {passkeysFeature, recoveryCodesRequireTwoFactorEnabled, setRecoveryCodesDialog} = useContext(TwoFactorContext);

    const [status, setStatus] = useState<"showing" | "adding" | "disabling" | null>(null);

    const [confirmingPasswordDialog, setConfirmingPasswordDialog] = useState<boolean>(false);

    const [passkeysArray, setPasskeysArray] = useState<Passkey[]>([]);

    const [twoFactorDialog, setTwoFactorDialog] = useState<"showingPasskeys" | "addingPasskey" | UpdatingPasskeyIdObject | DeletingPasskeyIdObject | null>(null);
    
    
    const [twoFactorAuthenticationEnabled, setTwoFactorAuthenticationEnabled] = useState<boolean>(passkeysFeature && passkeysFeature.userEnabled);

    const onPasswordConfimedMethod = useRef<"showPasskeys" | "disablePasskeys" | null>(null);

    const closeDialog = () => {
        if (passkeysFeature && !passkeysFeature.userEnabled) {
            setTwoFactorAuthenticationEnabled(false);
        }
        setPasskeysArray([]);
        setTwoFactorDialog(null);
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

    const removePasskeyForm = useForm();

    const submitAddPasskeyForm = async (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();

        setStatus("adding");

        if (! browserSupportsWebAuthn()) {
            addPasskeyForm.setError('name', "Your Browser Dosen't support passkeys");
            setStatus(null);
            return; 
        }

        const optionsResponse = await axios.get(passkeysTwoFactor.getRegisterOptions().url,{
            params: {name: addPasskeyForm.data.name},
            validateStatus: (status) => [200, 422].includes(status)
        });
        
        
        if (optionsResponse.status === 422) {
            addPasskeyForm.setError('name', optionsResponse.data.errors.name);
            setStatus(null);
            return; 
        }

        try {
            const passkey = await startRegistration({
                optionsJSON:optionsResponse.data
            });
            addPasskeyForm.setData('passkey', JSON.stringify(passkey))
        } catch (error) {
            addPasskeyForm.setError('name', "Passkey creation failed. please try again.");
            console.error(error);
            setStatus(null);
        }
    }

    useEffect(()=>{
        if (addPasskeyForm.data.passkey !== "") {    
            addPasskeyForm.post(passkeysTwoFactor.store().url,{
                errorBag: "createPasskey",
                onSuccess: () => Promise.all([
                    showPasskeys(true)
                ]),
                onFinish: () => {
                    setStatus(null)
                }
            })
        };
    }, [addPasskeyForm.data.passkey]);

    useEffect(()=>{
        if (isUpdatingPasskeyIdObject(twoFactorDialog)) {
            updatePasskeyForm.setData('name', twoFactorDialog.passkeyBeingUpdated.name)
        }
    }, [twoFactorDialog]);

    const submitUpdatePasskeyForm = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if(isUpdatingPasskeyIdObject(twoFactorDialog)) {
            updatePasskeyForm.put(passkeysTwoFactor.update(twoFactorDialog.passkeyBeingUpdated.id).url, {
                onSuccess: () => Promise.all([
                    showPasskeys()
                ]),
            })
        }
    }

    const submitRemovePasskeyForm = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        if(isDeletingPasskeyIdObject(twoFactorDialog)) {
            removePasskeyForm.delete(passkeysTwoFactor.destroy(twoFactorDialog.passkeyBeingDeleted.id).url, {
                onSuccess: () => Promise.all([
                    showPasskeys()
                ]),
            })
        }
    }

    const openPasswordDialog = () => {
        if (passkeysFeature && passkeysFeature.requirePasswordConfirmation) {
            axios.get(passwordConfirmation.show().url).then(response => {
                if (response.data.confirmed) {
                    onPasswordConfimed();
                } else {
                    setConfirmingPasswordDialog(true);
                }
            });
        } else {
            onPasswordConfimed();
        }
        
    };

    const onPasswordConfimed = () => {
        if (onPasswordConfimedMethod.current === 'showPasskeys') {
            showPasskeys();
        } else if (onPasswordConfimedMethod.current === 'disablePasskeys') {
            disablePasskeys();
        }
    }

    const showPasskeys = async (isPasskeyAddedRecently:boolean = false) => {
        setStatus("showing");

        try {
            const {data} = await axios.get(passkeysTwoFactor.get().url);

            setTwoFactorAuthenticationEnabled(true);

            setPasskeysArray(data.passkeys);

            setTwoFactorDialog("showingPasskeys");

            if (isPasskeyAddedRecently && recoveryCodesRequireTwoFactorEnabled && data.passkeys.length === 1)
                setRecoveryCodesDialog(true);

        } catch (error) {
            console.error(error)
        }

        setStatus(null);
    };

    const disablePasskeys = () => {
        setStatus("disabling");

        router.delete(passkeysTwoFactor.disable().url, {
            onSuccess: () => setTwoFactorAuthenticationEnabled(false),
            onFinish: () => setStatus(null)
        })
    };

    const checkboxChanged = (checked: boolean) => {
        if (checked) {
            onPasswordConfimedMethod.current = "showPasskeys"
            openPasswordDialog();
        } else {
            onPasswordConfimedMethod.current = "disablePasskeys"
            openPasswordDialog();
        }
    }

    return (
        <div>
            <h3 className="text-base font-medium text-gray-900 dark:text-gray-100 flex items-center justify-between">
                <span>Passkeys</span>
                
                <Switch
                    disabled={
                        browserSupportsWebAuthn() ?
                            (status === "showing") || (status === "disabling") :
                            true
                    }

                    checked={twoFactorAuthenticationEnabled}
                    onChange={(checked) => checkboxChanged(checked)}
                    className="group inline-flex h-6 w-11 items-center rounded-full bg-gray-200 dark:bg-gray-700 transition data-checked:bg-blue-600 data-checked:dark:bg-blue-600 cursor-pointer disabled:opacity-50"
                >
                    <span className="size-4 translate-x-1 rounded-full bg-white transition group-data-checked:translate-x-6" />
                </Switch>
            </h3>
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Use a passkey that inserts into your computer or syncs to your mobile device when you log in. You’ll need to use a supported mobile device or web browser.</p>

            {browserSupportsWebAuthn() ? (<>
                {(passkeysFeature && passkeysFeature.userEnabled) && (
                    <div className="mt-5.5 flex items-center gap-4.5">
                        <Button variant="default" onClick={() => {
                            onPasswordConfimedMethod.current = "showPasskeys"
                            openPasswordDialog();
                        }}>
                            Manage your passkeys
                        </Button>
                    </div>
                )}
                
                <Dialog open={twoFactorDialog == "showingPasskeys"} onOpenChange={(open) => !open && closeDialog()}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader className="flex items-center justify-center">
                            <DialogIcon icon="key" />
                            <DialogTitle>
                                Passkeys
                            </DialogTitle>
                            <DialogDescription className="text-center">
                                You need at least one passkey for this feature to be enabled
                            </DialogDescription>
                        </DialogHeader>
                        
                        <div className="flex flex-col items-center space-y-5">
                            <div className="shadow dark:!shadow-none overflow-hidden dark:border dark:border-white/20 rounded-lg w-full">
                                
                                <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-800">
                                    <thead className="bg-white dark:bg-slate-900">
                                        <tr>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                                Name
                                            </th>
                                            <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-200 uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="bg-gray-50 dark:bg-[#171717]">
                                        {(passkeysArray.length === 0) ? (
                                            <tr>
                                                <td colSpan={3} className="px-6 py-4 whitespace-nowrap text-center text-gray-900 dark:text-gray-100">
                                                    <em>No keys registered yet</em>
                                                </td>
                                            </tr>
                                        ) : (null)}
                                        {passkeysArray.map((key) => (
                                            <tr key={key.id}>
                                                <td className="px-6 py-4">
                                                    <div className="flex flex-col items-start">
                                                        <strong className="text-sm font-medium text-gray-900 dark:text-slate-100">
                                                            { key.name }
                                                        </strong>
                                                        <span className="text-xs">
                                                            Last use: { " " + (key.updated_at ?? "-") }
                                                        </span>
                                                    </div>
                                                </td>

                                                <td className="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div className="flex items-center space-x-3.5">
                                                        <button title="Delete" onClick={(e) => setTwoFactorDialog({passkeyBeingUpdated:key})} className="hover:text-primary">
                                                            <SquarePen className="size-4.5" strokeWidth={1.5} stroke="currentColor" fill="none"/>
                                                        </button>

                                                        <button title="Delete" onClick={(e) => setTwoFactorDialog({passkeyBeingDeleted:key})} className="hover:text-primary">
                                                            <Trash2 className="size-4.5" strokeWidth={1.5} stroke="currentColor" fill="none"/>
                                                        </button>
                                                    </div>

                                                </td>
                                            </tr>
                                        ))}

                                    </tbody>
                                </table>

                            </div>

                            <div className="flex w-full space-x-5">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="flex-1"
                                    onClick={() => closeDialog()}
                                >
                                    Close
                                </Button>
                                <Button
                                    type="button"
                                    className="flex-1"
                                    onClick={() => {
                                        setTwoFactorDialog("addingPasskey");
                                    }}
                                >
                                    Add a new key
                                </Button>
                            </div>
                        </div>
                    </DialogContent>
                </Dialog>

                <Dialog open={twoFactorDialog == "addingPasskey"} onOpenChange={(open) => !open && closeDialog()}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader className="flex items-center justify-center">
                            <DialogIcon icon="key" />
                            <DialogTitle>
                                Create a new Passkey
                            </DialogTitle>
                            <DialogDescription className="text-center">
                                Please enter the name of the new passkey
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={(e) => submitAddPasskeyForm(e)} className="flex flex-col items-center space-y-5">

                            <div className="relative w-full space-y-3">
                                <div className="flex w-full flex-col items-center space-y-3 py-2">
                                    <Input
                                        id="keyname"
                                        type="text"
                                        name="keyname"
                                        className="w-full"
                                        autoFocus
                                        autoComplete='keyname'
                                        placeholder="Key name"
                                        onChange={(e) => addPasskeyForm.setData('name', e.target.value)}
                                        disabled={addPasskeyForm.processing}
                                    />
                                    <InputError message={addPasskeyForm.errors.name} />
                                    <InputError message={addPasskeyForm.errors.passkey} />
                                </div>
    
                                <div className="flex w-full space-x-5">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="flex-1"
                                        onClick={() => {
                                            setTwoFactorDialog("showingPasskeys");
                                        }}
                                        disabled={addPasskeyForm.processing}
                                    >
                                        Back
                                    </Button>
                                    <Button
                                        type="submit"
                                        className="flex-1"
                                        disabled={addPasskeyForm.processing || status === "adding"}
                                    >
                                        Add
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>

                <Dialog open={isUpdatingPasskeyIdObject(twoFactorDialog) ? twoFactorDialog.passkeyBeingUpdated != null : false} onOpenChange={(open) => !open && closeDialog()}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader className="flex items-center justify-center">
                            <DialogIcon icon="key" />
                            <DialogTitle>
                                Update Passkey
                            </DialogTitle>
                            <DialogDescription className="text-center">
                                Update the name of this passkey
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={(e) => submitUpdatePasskeyForm(e)} className="flex flex-col items-center space-y-5">
                        
                            <div className="relative w-full space-y-3">
                                <div className="flex w-full flex-col items-center space-y-3 py-2">
                                    <Input
                                        id="keyname"
                                        type="text"
                                        name="keyname"
                                        className="w-full"
                                        autoFocus
                                        autoComplete='keyname'
                                        placeholder="Key name"
                                        onChange={(e) => updatePasskeyForm.setData('name', e.target.value)}
                                        disabled={updatePasskeyForm.processing}
                                    />
                                    <InputError message={updatePasskeyForm.errors.name} />
                                </div>
    
                                <div className="flex w-full space-x-5">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="flex-1"
                                        onClick={() => {
                                            setTwoFactorDialog("showingPasskeys");
                                        }}
                                        disabled={updatePasskeyForm.processing}
                                    >
                                        Back
                                    </Button>
                                    <Button
                                        type="submit"
                                        className="flex-1"
                                        disabled={updatePasskeyForm.processing || status === "adding"}
                                    >
                                        Update
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>

                <Dialog open={isDeletingPasskeyIdObject(twoFactorDialog) ? twoFactorDialog.passkeyBeingDeleted != null : false} onOpenChange={(open) => !open && closeDialog()}>
                    <DialogContent className="sm:max-w-md">
                        <DialogHeader className="flex items-center justify-center">
                            <DialogIcon icon="key" />
                            <DialogTitle>
                                Delete Passkey
                            </DialogTitle>
                            <DialogDescription className="text-center">
                                Are you sure you would like to delete this passkey?
                            </DialogDescription>
                        </DialogHeader>

                        <form onSubmit={(e) => submitRemovePasskeyForm(e)} className="flex flex-col items-center space-y-5">
                        
                            <div className="relative w-full space-y-3">
                                <div className="flex w-full space-x-5">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        className="flex-1"
                                        onClick={() => {
                                            setTwoFactorDialog("showingPasskeys");
                                        }}
                                        disabled={removePasskeyForm.processing}
                                    >
                                        Back
                                    </Button>
                                    <Button
                                        type="submit"
                                        className="flex-1"
                                        variant="destructive"
                                        disabled={removePasskeyForm.processing || status === "adding"}
                                    >
                                        Delete
                                    </Button>
                                </div>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>

                <ConfirmPasswordDialog
                    confirmingPasswordDialog={confirmingPasswordDialog}
                    setConfirmingPasswordDialog={setConfirmingPasswordDialog}
                    onPasswordConfimed={onPasswordConfimed}
                />
            </>) : (
                <p className="mt-1 text-sm text-red-500">Your browser doesn’t currently support passkeys.</p>
            )}

            
        </div>
    );
}

const RecoveryCodes = ({}) => {
    const {recoveryCodesFeature, recoveryCodesRequireTwoFactorEnabled, recoveryCodesDialog, setRecoveryCodesDialog} = useContext(TwoFactorContext);

    const [status, setStatus] = useState<"enabling"  | "disabling" | null>(null);

    const [confirmingPasswordDialog, setConfirmingPasswordDialog] = useState<boolean>(false);

    const [recoveryCodesArray, setRecoveryCodesArray] = useState<string[]>([]);

    useEffect(()=>{
        if (recoveryCodesDialog === true) {
            showRecoveryCodesArray();
        }
    }, [recoveryCodesDialog]);

    const onPasswordConfimedMethod = useRef<"showRecoveryCodes" | "disableRecoveryCods" | "regenerateRecoveryCodes" | null>(null);

    const [twoFactorRecoveryCodesEnabled, setTwoFactorRecoveryCodesEnabled] = useState(recoveryCodesFeature && recoveryCodesFeature.userEnabled);

    const recoveryCodesDiv = useRef<HTMLDivElement>(null);

    const openPasswordDialog = () => {

        if (recoveryCodesFeature && recoveryCodesFeature.confirmsPasswordRecoveryCode) {
            axios.get(passwordConfirmation.show().url).then(response => {
                if (response.data.confirmed) {
                    onPasswordConfimed();
                } else {
                    setConfirmingPasswordDialog(true);
                }
            });
        } else {
            onPasswordConfimed();
        }
        
    };

    const onPasswordConfimed = () => {
        if (onPasswordConfimedMethod.current === 'regenerateRecoveryCodes') {
            regenerateRecoveryCodes();
        } else if (onPasswordConfimedMethod.current === 'showRecoveryCodes') {
            setRecoveryCodesDialog(true);
        } else if (onPasswordConfimedMethod.current === 'disableRecoveryCods') {
            disableRecoveryCods();
        }
    }

    const showRecoveryCodesArray = async () => {
        const response = await axios.get(twoFactorRecoveryCodes.get().url);
        if (response.data.length > 0) {
            setRecoveryCodesArray(response.data);
            setTwoFactorRecoveryCodesEnabled(true);
        }
    };

    const regenerateRecoveryCodes = () => {
        setStatus("enabling");
        router.post(twoFactorRecoveryCodes.generate().url, {},{
            preserveScroll: true,
            onSuccess: () => {
                if (!recoveryCodesDialog) {
                    setRecoveryCodesDialog(true);
                } else {
                    showRecoveryCodesArray();
                }
            },
            onFinish: () => {
                setStatus(null);
            }
        })
    };

    const disableRecoveryCods = () => {
        setStatus("disabling");
    
        router.delete(twoFactorRecoveryCodes.disable().url, {
            preserveScroll: true,
            onSuccess: () => {
                setTwoFactorRecoveryCodesEnabled(false);
                setRecoveryCodesArray([]);
            },
            onFinish: () => {
                setStatus(null);
            }
        });
    };

    const copyRecoveryCods = (e :React.MouseEvent<HTMLButtonElement, MouseEvent>) => {
        const copyButton = e.currentTarget;

        const clipboardSvg = copyButton.querySelector('svg');
        const clipboardCopiedSvg = copyButton.querySelector('svg:nth-child(2)');

        copyButton.classList.add('!opacity-100')
        clipboardSvg?.classList.add('hidden');
        clipboardCopiedSvg?.classList.remove('hidden');

        setTimeout(()=>{
            copyButton.classList.remove('!opacity-100')
            clipboardSvg?.classList.remove('hidden');
            clipboardCopiedSvg?.classList.add('hidden');
        }, 1000);

        navigator.clipboard.writeText(recoveryCodesDiv.current ? recoveryCodesDiv.current.innerText : "");
    };

    const checkboxChanged = (checked: boolean) => {

        if (checked) {
            onPasswordConfimedMethod.current = "regenerateRecoveryCodes";
            openPasswordDialog();
        } else {
            onPasswordConfimedMethod.current = "disableRecoveryCods";
            openPasswordDialog();
        }

    }

    return (
        <div>
            <h3 className="text-base font-medium text-gray-900 dark:text-gray-100 flex items-center justify-between">
                <span>Recovery codes</span>
                
                {(! recoveryCodesRequireTwoFactorEnabled) && (
                    <Switch
                        disabled={(status === "enabling") || (status === "disabling")}

                        checked={twoFactorRecoveryCodesEnabled}
                        onChange={(checked) => checkboxChanged(checked)}
                        className="group inline-flex h-6 w-11 items-center rounded-full bg-gray-200 dark:bg-gray-700 transition data-checked:bg-blue-600 data-checked:dark:bg-blue-600 cursor-pointer disabled:opacity-50"
                    >
                        <span className="size-4 translate-x-1 rounded-full bg-white transition group-data-checked:translate-x-6" />
                    </Switch>
                )}
            </h3>
            <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">Single-use recovery codes that lets you log in if you don’t have access to your two-factor authentication options.</p>

            <div className="mt-5.5 flex items-center gap-4.5">
                <Button variant="default" onClick={(e) => {
                    onPasswordConfimedMethod.current = "showRecoveryCodes"
                    openPasswordDialog();
                }}>
                    Show Recovery Codes
                </Button>
            </div>

            <Dialog open={recoveryCodesDialog && (twoFactorRecoveryCodesEnabled && recoveryCodesArray.length > 0 )} onOpenChange={(open) => !open && setRecoveryCodesDialog(false)}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader className="flex items-center justify-center">
                        <DialogIcon icon="code" />
                        <DialogTitle>
                            Recovery Cods
                        </DialogTitle>
                        <DialogDescription className="text-center">
                            Store these recovery codes in a secure password manager
                        </DialogDescription>
                    </DialogHeader>

                    <div className="flex flex-col items-center space-y-5">
                        <div className="relative w-full space-y-3">
                            <div className="flex w-full flex-col items-center space-y-3 py-2">
                                <div className="rounded-lg bg-muted p-4 font-mono text-sm flex justify-between w-full">
                                    <div ref={recoveryCodesDiv}>
                                        {recoveryCodesArray.map((code) => (
                                            <div key={code}>
                                                {code}
                                            </div>
                                        ))}
                                    </div>
                                    <button type="button" className="h-fit opacity-30 hover:opacity-50 focus-visible:outline-none" onClick={(e) => copyRecoveryCods(e)}>
                                        <Clipboard/>
                                        <ClipboardCheck className="hidden"/>
                                    </button>
                                </div>

                                <DialogDescription>
                                    If you ever lose access to your two-factor authentication options, you can use these codes to verify your identity.
                                </DialogDescription>
                            </div>

                            <div className="flex w-full space-x-5">
                                <Button
                                    type="button"
                                    variant="outline"
                                    className="flex-1"
                                    onClick={() => setRecoveryCodesDialog(false)}
                                >
                                    Close
                                </Button>
                                <Button
                                    type="button"
                                    className="flex-1"
                                    onClick={(e) =>{
                                        onPasswordConfimedMethod.current = "regenerateRecoveryCodes"
                                        openPasswordDialog();
                                    }}
                                    disabled={status === "enabling"}
                                >
                                    Regenerate Codes
                                </Button>
                            </div>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>

            <ConfirmPasswordDialog
                confirmingPasswordDialog={confirmingPasswordDialog}
                setConfirmingPasswordDialog={setConfirmingPasswordDialog}
                onPasswordConfimed={onPasswordConfimed}
            />
        </div>
    );
}
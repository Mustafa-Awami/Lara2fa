import { useForm } from '@inertiajs/react';

import { SetStateAction, useRef } from 'react';

import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import passwordConfirmation from '@/routes/password-confirmation';
import { RectangleEllipsis } from 'lucide-react';

const DialogIcon = () => {
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
                <RectangleEllipsis className="relative z-20 size-6 text-foreground" />
            </div>
        </div>
    );
}

export default function ConfirmPasswordDialog ({
    confirmingPasswordDialog,
    setConfirmingPasswordDialog,
    onPasswordConfimed
}: {
    confirmingPasswordDialog: boolean
    setConfirmingPasswordDialog: React.Dispatch<SetStateAction<boolean>>
    onPasswordConfimed: () => void
}) {
    const passwordInput = useRef<HTMLInputElement>(null);

    const passwordConfirmationForm = useForm({
        password: '',
    });

    const confirmPassword = (e :React.FormEvent<HTMLFormElement> ) => {
        e.preventDefault();

        passwordConfirmationForm.post(passwordConfirmation.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                onPasswordConfimed();
                closePasswordDialog();
            },
            onError: () => passwordInput.current?.focus(),
        });
        
    };

    const closePasswordDialog = () => {
        setConfirmingPasswordDialog(false);

        passwordConfirmationForm.resetAndClearErrors();
    };

    return (
        <Dialog open={confirmingPasswordDialog} onOpenChange={closePasswordDialog}>
            <DialogContent className="sm:max-w-sm">
                <DialogHeader className="flex items-center justify-center">
                    <DialogIcon />
                    <DialogTitle>
                        Confirm Password
                    </DialogTitle>
                    <DialogDescription className="text-center">
                        For your security, please confirm your password.
                    </DialogDescription>
                </DialogHeader>

                <form onSubmit={confirmPassword} className="flex flex-col items-center space-y-5">
                    <div className="relative w-full space-y-3">
                        <div className="flex w-full flex-col items-center space-y-3 py-2">
                            <Label htmlFor="password" className="sr-only">Password</Label>

                            <Input
                                id="password"
                                type="password"
                                name="password"
                                ref={passwordInput}
                                value={passwordConfirmationForm.data.password}
                                onChange={(e) => passwordConfirmationForm.setData('password', e.target.value)}
                                className="w-full"
                                placeholder="Password"
                            />

                            <InputError message={passwordConfirmationForm.errors.password} />
                        </div>

                        <div className="flex w-full space-x-5">
                            <Button
                                type="button"
                                variant="outline"
                                className="flex-1"
                                onClick={() => {closePasswordDialog()}}
                                disabled={passwordConfirmationForm.processing}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                className="flex-1"
                                disabled={passwordConfirmationForm.processing}
                            >
                                Confirm
                            </Button>
                        </div>
                    </div>
                </form>
            </DialogContent>
        </Dialog>
    );
}
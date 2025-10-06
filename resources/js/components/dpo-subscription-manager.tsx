import React, { JSX } from "react";
import {
  AlertCircle,
  Check,
  X,
  Loader2,
  CreditCard,
  Smartphone,
} from "lucide-react";

type SubscriptionStatus = "active" | "paused" | "cancelled" | "expired";

interface Subscription {
  id: string;
  description?: string;
  subscription_reference: string;
  status: SubscriptionStatus;
  formatted_amount: string;
  frequency: string;
  next_billing_date?: string;
  currency: string;
  total_paid: number;
}

interface StatusBadgeConfig {
  color: string;
  label: string;
}

interface DpoSubscriptionManagerProps {
  subscriptions?: Subscription[];
  onCancel?: (subscription: Subscription) => void;
  onPause?: (subscription: Subscription) => void;
  onResume?: (subscription: Subscription) => void;
}

export const DpoSubscriptionManager: React.FC<DpoSubscriptionManagerProps> = ({
  subscriptions = [],
  onCancel,
  onPause,
  onResume,
}) => {
  const getStatusBadge = (status: SubscriptionStatus): JSX.Element => {
    const configs: Record<SubscriptionStatus, StatusBadgeConfig> = {
      active: { color: "bg-green-100 text-green-800", label: "Active" },
      paused: { color: "bg-yellow-100 text-yellow-800", label: "Paused" },
      cancelled: { color: "bg-red-100 text-red-800", label: "Cancelled" },
      expired: { color: "bg-gray-100 text-gray-800", label: "Expired" },
    };

    const config: StatusBadgeConfig = configs[status] || configs.expired;
    return (
      <span
        className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${config.color}`}
      >
        {config.label}
      </span>
    );
  };

  return (
    <div className="space-y-4">
      {subscriptions.map((subscription) => (
        <div key={subscription.id} className="bg-white rounded-lg shadow p-6">
          <div className="flex justify-between items-start mb-4">
            <div>
              <h3 className="text-lg font-semibold text-gray-900">
                {subscription.description || "Subscription"}
              </h3>
              <p className="text-sm text-gray-500">
                Reference: {subscription.subscription_reference}
              </p>
            </div>
            {getStatusBadge(subscription.status)}
          </div>

          <div className="grid md:grid-cols-3 gap-4 mb-4">
            <div>
              <p className="text-sm text-gray-500">Amount</p>
              <p className="font-semibold">
                {subscription.formatted_amount}/{subscription.frequency}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Next Billing</p>
              <p className="font-semibold">
                {subscription.next_billing_date
                  ? new Date(
                      subscription.next_billing_date
                    ).toLocaleDateString()
                  : "-"}
              </p>
            </div>
            <div>
              <p className="text-sm text-gray-500">Total Paid</p>
              <p className="font-semibold">
                {subscription.currency} {subscription.total_paid}
              </p>
            </div>
          </div>

          <div className="flex space-x-3">
            {subscription.status === "active" && (
              <>
                <button
                  onClick={() => onPause?.(subscription)}
                  className="px-4 py-2 text-sm text-yellow-600 hover:text-yellow-700 border border-yellow-600 rounded-md"
                >
                  Pause
                </button>
                <button
                  onClick={() => onCancel?.(subscription)}
                  className="px-4 py-2 text-sm text-red-600 hover:text-red-700 border border-red-600 rounded-md"
                >
                  Cancel
                </button>
              </>
            )}
            {subscription.status === "paused" && (
              <button
                onClick={() => onResume?.(subscription)}
                className="px-4 py-2 text-sm text-green-600 hover:text-green-700 border border-green-600 rounded-md"
              >
                Resume
              </button>
            )}
          </div>
        </div>
      ))}
    </div>
  );
};

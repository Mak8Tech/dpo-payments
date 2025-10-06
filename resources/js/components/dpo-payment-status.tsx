import React, { useState, useEffect } from "react";
import {
  AlertCircle,
  Check,
  X,
  Loader2,
  CreditCard,
  Smartphone,
  LucideIcon,
} from "lucide-react";

type PaymentStatus = "success" | "pending" | "failed" | "cancelled";

interface Transaction {
  reference: string;
  status: PaymentStatus;
  formatted_amount: string;
  customer_email?: string;
  dpo_result_explanation?: string;
}

interface StatusResponse {
  transaction: Transaction;
  error?: string;
}

interface StatusConfig {
  color: string;
  icon: LucideIcon;
  message: string;
}

interface DpoPaymentStatusProps {
  reference: string;
  autoRefresh?: boolean;
}

export const DpoPaymentStatus: React.FC<DpoPaymentStatusProps> = ({
  reference,
  autoRefresh = true,
}) => {
  const [status, setStatus] = useState<Transaction | null>(null);
  const [loading, setLoading] = useState<boolean>(true);
  const [error, setError] = useState<string>("");

  useEffect(() => {
    if (reference) {
      fetchStatus();

      if (autoRefresh && status?.status === "pending") {
        const interval = setInterval(fetchStatus, 5000);
        return () => clearInterval(interval);
      }
    }
  }, [reference, autoRefresh, status?.status]);

  const fetchStatus = async (): Promise<void> => {
    try {
      const response = await fetch(`/api/dpo/payments/${reference}/status`);
      const data: StatusResponse = await response.json();

      if (data.error) {
        throw new Error(data.error);
      }

      setStatus(data.transaction);
    } catch (err) {
      const errorMessage =
        err instanceof Error ? err.message : "An error occurred";
      setError(errorMessage);
    } finally {
      setLoading(false);
    }
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center p-8">
        <Loader2 className="animate-spin h-8 w-8 text-indigo-600" />
      </div>
    );
  }

  if (error) {
    return (
      <div className="p-6 bg-red-50 border border-red-200 rounded-lg">
        <div className="flex items-center">
          <X className="h-6 w-6 text-red-600 mr-2" />
          <span className="text-red-800">{error}</span>
        </div>
      </div>
    );
  }

  const statusConfig: Record<PaymentStatus, StatusConfig> = {
    success: {
      color: "green",
      icon: Check,
      message: "Payment Successful",
    },
    pending: {
      color: "yellow",
      icon: Loader2,
      message: "Payment Pending",
    },
    failed: {
      color: "red",
      icon: X,
      message: "Payment Failed",
    },
    cancelled: {
      color: "gray",
      icon: X,
      message: "Payment Cancelled",
    },
  };

  const config: StatusConfig =
    statusConfig[status?.status as PaymentStatus] || statusConfig.pending;
  const Icon = config.icon;

  return (
    <div
      className={`p-6 bg-${config.color}-50 border border-${config.color}-200 rounded-lg`}
    >
      <div className="flex items-center mb-4">
        <Icon
          className={`h-6 w-6 text-${config.color}-600 mr-2 ${
            status?.status === "pending" ? "animate-spin" : ""
          }`}
        />
        <h3 className={`text-lg font-semibold text-${config.color}-900`}>
          {config.message}
        </h3>
      </div>

      <div className="space-y-2 text-sm">
        <div className="flex justify-between">
          <span className="text-gray-600">Reference:</span>
          <span className="font-mono">{status?.reference}</span>
        </div>
        <div className="flex justify-between">
          <span className="text-gray-600">Amount:</span>
          <span>{status?.formatted_amount}</span>
        </div>
        {status?.customer_email && (
          <div className="flex justify-between">
            <span className="text-gray-600">Email:</span>
            <span>{status.customer_email}</span>
          </div>
        )}
        {status?.dpo_result_explanation && (
          <div className="mt-4 p-3 bg-gray-100 rounded">
            <span className="text-gray-700">
              {status.dpo_result_explanation}
            </span>
          </div>
        )}
      </div>
    </div>
  );
};
